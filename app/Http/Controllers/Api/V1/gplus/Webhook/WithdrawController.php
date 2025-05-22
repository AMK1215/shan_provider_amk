<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Enums\SeamlessWalletCode;
use App\Services\WalletService;
use App\Enums\TransactionName;
use App\Models\PlaceBet; // Import PlaceBet model
use Illuminate\Support\Facades\DB; // Import DB facade for transactions
use Bavix\Wallet\Models\Transaction as WalletTransaction; // Alias for Laravel Wallet's Transaction model

class WithdrawController extends Controller
{
    protected $walletService;
    private array $allowedCurrencies = ['IDR', 'IDR2', 'KRW2', 'MMK2', 'VND2', 'LAK2', 'KHR2'];

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function withdraw(Request $request)
    {
        Log::info('Withdraw API Request', ['request' => $request->all()]);

        try {
            $request->validate([
                'operator_code' => 'required|string',
                'batch_requests' => 'required|array',
                'sign' => 'required|string',
                'request_time' => 'required|integer',
                'currency' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Withdraw API Validation Failed', ['errors' => $e->errors()]);
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'withdraw' .
            $secretKey
        );

        $isValidSign = strtolower($request->sign) === strtolower($expectedSign);
        $isValidCurrency = in_array($request->currency, $this->allowedCurrencies);

        $responseData = [];

        foreach ($request->batch_requests as $batchRequest) {
            $memberAccount = $batchRequest['member_account'] ?? null;
            $productCode = $batchRequest['product_code'] ?? null;
            $gameType = $batchRequest['game_type'] ?? ''; // Added game_type from batchRequest
            $transactions = $batchRequest['transactions'] ?? [];

            // Handle batch-level errors (invalid signature or currency)
            if (!$isValidSign) {
                Log::warning('Invalid signature for batch', ['member_account' => $memberAccount, 'provided' => $request->sign, 'expected' => $expectedSign]);
                $responseData[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::InvalidSignature, 'Invalid signature');
                // We don't log to place_bets here as it's a request-level signature issue, not a transaction issue.
                continue;
            }

            if (!$isValidCurrency) {
                Log::warning('Invalid currency for batch', ['member_account' => $memberAccount, 'currency' => $request->currency]);
                $responseData[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::InternalServerError, 'Invalid Currency');
                // We don't log to place_bets here as it's a request-level currency issue.
                continue;
            }

            $user = User::where('user_name', $memberAccount)->with('wallet')->first();

            if (!$user) {
                Log::warning('Member not found for withdraw/bet request', ['member_account' => $memberAccount]);
                $responseData[] = [
                    'member_account' => $memberAccount,
                    'product_code' => $productCode,
                    'before_balance' => number_format(0.00, 2, '.', ''),
                    'balance' => number_format(0.00, 2, '.', ''),
                    'code' => SeamlessWalletCode::MemberNotExist->value,
                    'message' => 'Member not found',
                ];
                // No specific transaction to log in place_bets for a non-existent user at this point
                continue;
            }

            foreach ($transactions as $tx) {
                $transactionId = $tx['id'] ?? null;
                $action = $tx['action'] ?? null;
                $amount = $tx['amount'] ?? null;
                $wagerCode = $tx['wager_code'] ?? null;

                // Initial check for missing crucial data
                if (!$transactionId || !$action || $amount === null) {
                    Log::warning('Missing crucial data in transaction for withdraw/bet', ['tx' => $tx]);
                    $this->logPlaceBet($batchRequest, $request, $tx, 'failed', $request->request_time, 'Missing transaction data (id, action, or amount)'); // Log failure
                    $responseData[] = [
                        'member_account' => $memberAccount,
                        'product_code' => $productCode,
                        'before_balance' => number_format($user->balanceFloat, 2, '.', ''),
                        'balance' => number_format($user->balanceFloat, 2, '.', ''),
                        'code' => SeamlessWalletCode::InternalServerError->value,
                        'message' => 'Missing transaction data (id, action, or amount)',
                    ];
                    continue;
                }

                $currentBalance = $user->balanceFloat;
                $newBalance = $currentBalance;
                $transactionCode = SeamlessWalletCode::Success->value;
                $transactionMessage = '';

                $meta = [
                    'seamless_transaction_id' => $transactionId,
                    'action_type' => $action,
                    'product_code' => $productCode,
                    'wager_code' => $wagerCode,
                    'round_id' => $tx['round_id'] ?? null,
                    'game_code' => $tx['game_code'] ?? null,
                    'channel_code' => $tx['channel_code'] ?? null,
                    'raw_payload' => $tx,
                ];

                // Check for duplicate transaction before processing
                // Check in PlaceBet table
                $duplicateInPlaceBets = PlaceBet::where('transaction_id', $transactionId)->first();
                // Check in Wallet's internal transactions (meta->seamless_transaction_id)
                $duplicateInWalletTransactions = WalletTransaction::whereJsonContains('meta->seamless_transaction_id', $transactionId)->first();

                if ($duplicateInPlaceBets || $duplicateInWalletTransactions) {
                    Log::warning('Duplicate transaction ID detected for withdraw/bet', ['tx_id' => $transactionId, 'member_account' => $memberAccount]);
                    $this->logPlaceBet($batchRequest, $request, $tx, 'duplicate', $request->request_time, 'Duplicate transaction'); // Log duplicate attempt
                    $responseData[] = $this->buildErrorResponse($memberAccount, $productCode, $currentBalance, SeamlessWalletCode::DuplicateTransaction, 'Duplicate transaction');
                    continue; // Skip processing this duplicate transaction
                }

                // Start a database transaction for each individual transaction request
                DB::beginTransaction();
                try {
                    // Re-fetch user and lock wallet inside transaction for isolation
                    $user->refresh(); // Get the latest state of the user and their wallet
                    $user->wallet->lockForUpdate();
                    $beforeTransactionBalance = $user->wallet->balanceFloat;

                    if ($action === 'BET') {
                        $betAmount = abs($amount);

                        if ($betAmount <= 0) {
                            $transactionCode = SeamlessWalletCode::InternalServerError->value;
                            $transactionMessage = 'Bet amount must be positive and greater than zero.';
                            Log::warning('Invalid bet amount received', ['transaction_id' => $transactionId, 'amount' => $amount]);
                        } elseif ($user->balanceFloat < $betAmount) {
                            $transactionCode = SeamlessWalletCode::InsufficientBalance->value;
                            $transactionMessage = 'Insufficient balance';
                            Log::warning('Insufficient balance for bet', ['member_account' => $memberAccount, 'bet_amount' => $betAmount, 'current_balance' => $currentBalance]);
                        } else {
                            $this->walletService->withdraw($user, $betAmount, TransactionName::Stake, $meta);
                            $newBalance = $user->balanceFloat;
                            Log::info('Successfully processed bet transaction via WalletService', ['transaction_id' => $transactionId, 'member_account' => $memberAccount, 'bet_amount' => $betAmount, 'new_balance' => $newBalance]);
                        }
                    } else {
                        $transactionCode = SeamlessWalletCode::InternalServerError->value;
                        $transactionMessage = 'Unsupported action type for this endpoint: ' . $action;
                        Log::warning('Unsupported action type received on withdraw endpoint', ['transaction_id' => $transactionId, 'action' => $action]);
                    }

                    // Determine final status for PlaceBet logging
                    $finalStatus = ($transactionCode === SeamlessWalletCode::Success->value) ? 'completed' : 'failed';
                    $this->logPlaceBet($batchRequest, $request, $tx, $finalStatus, $request->request_time, $transactionMessage);

                    DB::commit(); // Commit inner transaction

                } catch (\Bavix\Wallet\Exceptions\InsufficientFunds $e) {
                    DB::rollBack(); // Rollback inner transaction
                    $transactionCode = SeamlessWalletCode::InsufficientBalance->value;
                    $transactionMessage = 'Insufficient balance (Wallet package)';
                    Log::error('Wallet Insufficient Funds for bet', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                    $this->logPlaceBet($batchRequest, $request, $tx, 'failed', $request->request_time, $transactionMessage); // Log failure
                } catch (\Throwable $e) {
                    DB::rollBack(); // Rollback inner transaction
                    $transactionCode = SeamlessWalletCode::InternalServerError->value;
                    $transactionMessage = 'Failed to process bet transaction: ' . $e->getMessage();
                    Log::error('Error processing bet transaction via WalletService', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                    $this->logPlaceBet($batchRequest, $request, $tx, 'failed', $request->request_time, $transactionMessage); // Log failure
                }

                $responseData[] = [
                    'member_account' => $memberAccount,
                    'product_code' => $productCode,
                    'before_balance' => number_format($currentBalance, 2, '.', ''),
                    'balance' => number_format($newBalance, 2, '.', ''),
                    'code' => $transactionCode,
                    'message' => $transactionMessage,
                ];
            }
        }

        return response()->json([
            'code' => SeamlessWalletCode::Success->value,
            'message' => 'Processed batch requests',
            'data' => $responseData,
        ]);
    }

    /**
     * Helper to build a consistent error response.
     */
    private function buildErrorResponse(string $memberAccount, string $productCode, float $balance, SeamlessWalletCode $code, string $message): array
    {
        return [
            'member_account' => $memberAccount,
            'product_code' => $productCode,
            'before_balance' => number_format($balance, 2, '.', ''),
            'balance' => number_format($balance, 2, '.', ''),
            'code' => $code->value,
            'message' => $message,
        ];
    }

    /**
     * Logs the transaction attempt in the place_bets table using updateOrCreate.
     *
     * @param array $batchRequest
     * @param Request $fullRequest
     * @param array $transactionRequest
     * @param string $status 'completed', 'failed', 'duplicate', etc.
     * @param int|null $requestTime The original request_time from the full request (milliseconds)
     * @param string|null $errorMessage
     * @return void
     */
    private function logPlaceBet(array $batchRequest, Request $fullRequest, array $transactionRequest, string $status, ?int $requestTime, ?string $errorMessage = null): void
    {
        // Convert milliseconds to seconds if necessary for timestamp columns
        $requestTimeInSeconds = $requestTime ? floor($requestTime / 1000) : null;
        $settleAtTime = $transactionRequest['settle_at'] ?? $transactionRequest['settled_at'] ?? null;
        $settleAtInSeconds = $settleAtTime ? floor($settleAtTime / 1000) : null;
        $createdAtProviderTime = $transactionRequest['created_at'] ?? null;
        $createdAtProviderInSeconds = $createdAtProviderTime ? floor($createdAtProviderTime / 1000) : null;


        PlaceBet::updateOrCreate(
            ['transaction_id' => $transactionRequest['id'] ?? ''], // Key for finding existing record
            [
                // Batch-level data (from the main $request and $batchRequest)
                'member_account'        => $batchRequest['member_account'] ?? '',
                'product_code'          => $batchRequest['product_code'] ?? 0,
                'game_type'             => $batchRequest['game_type'] ?? '',
                'operator_code'         => $fullRequest->operator_code,
                'request_time'          => $requestTimeInSeconds ? now()->setTimestamp($requestTimeInSeconds) : null,
                'sign'                  => $fullRequest->sign,
                'currency'              => $fullRequest->currency,

                // Transaction-level data (from $transactionRequest)
                'action'                => $transactionRequest['action'] ?? '',
                'amount'                => $transactionRequest['amount'] ?? 0,
                'valid_bet_amount'      => $transactionRequest['valid_bet_amount'] ?? null,
                'bet_amount'            => $transactionRequest['bet_amount'] ?? null,
                'prize_amount'          => $transactionRequest['prize_amount'] ?? null,
                'tip_amount'            => $transactionRequest['tip_amount'] ?? null,
                'wager_code'            => $transactionRequest['wager_code'] ?? null,
                'wager_status'          => $transactionRequest['wager_status'] ?? null,
                'round_id'              => $transactionRequest['round_id'] ?? null,
                'payload'               => isset($transactionRequest['payload']) ? json_encode($transactionRequest['payload']) : null,
                'settle_at'             => $settleAtInSeconds ? now()->setTimestamp($settleAtInSeconds) : null,
                'created_at_provider'   => $createdAtProviderInSeconds ? now()->setTimestamp($createdAtProviderInSeconds) : null, // Assuming this field exists and is needed
                'game_code'             => $transactionRequest['game_code'] ?? null,
                'channel_code'          => $transactionRequest['channel_code'] ?? null,
                'status'                => $status, // 'completed', 'failed', 'duplicate', etc.
                //'error_message'         => $errorMessage, // Store the error message
            ]
        );
    }
}