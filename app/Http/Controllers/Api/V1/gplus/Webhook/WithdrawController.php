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

class WithdrawController extends Controller
{
    protected $walletService;
    private array $allowedCurrencies = ['IDR', 'IDR2', 'KRW2', 'MMK2', 'VND2', 'LAK2', 'KHR2']; // Define allowed currencies

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
            $transactions = $batchRequest['transactions'] ?? [];

            // Handle batch-level errors (invalid signature or currency)
            // These checks are now inside the foreach loop for batch_requests
            if (!$isValidSign) {
                Log::warning('Invalid signature for batch', ['member_account' => $memberAccount, 'provided' => $request->sign, 'expected' => $expectedSign]);
                $responseData[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::InvalidSignature, 'Invalid signature');
                continue; // Skip to the next batch request
            }

            if (!$isValidCurrency) {
                Log::warning('Invalid currency for batch', ['member_account' => $memberAccount, 'currency' => $request->currency]);
                $responseData[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::InternalServerError, 'Invalid Currency');
                continue; // Skip to the next batch request
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
                continue;
            }

            foreach ($transactions as $tx) {
                $transactionId = $tx['id'] ?? null;
                $action = $tx['action'] ?? null;
                $amount = $tx['amount'] ?? null;
                $wagerCode = $tx['wager_code'] ?? null;

                if (!$transactionId || !$action || $amount === null) {
                    Log::warning('Missing crucial data in transaction for withdraw/bet', ['tx' => $tx]);
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
                        try {
                            $this->walletService->withdraw($user, $betAmount, TransactionName::Stake, $meta);
                            $newBalance = $user->balanceFloat;
                            Log::info('Successfully processed bet transaction via WalletService', ['transaction_id' => $transactionId, 'member_account' => $memberAccount, 'bet_amount' => $betAmount, 'new_balance' => $newBalance]);
                        } catch (\Bavix\Wallet\Exceptions\InsufficientFunds $e) {
                             $transactionCode = SeamlessWalletCode::InsufficientBalance->value;
                             $transactionMessage = 'Insufficient balance (Wallet package)';
                             Log::error('Wallet Insufficient Funds for bet', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                        } catch (\Throwable $e) {
                            $transactionCode = SeamlessWalletCode::InternalServerError->value;
                            $transactionMessage = 'Failed to process bet transaction: ' . $e->getMessage();
                            Log::error('Error processing bet transaction via WalletService', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                        }
                    }
                } else {
                    $transactionCode = SeamlessWalletCode::InternalServerError->value;
                    $transactionMessage = 'Unsupported action type for this endpoint: ' . $action;
                    Log::warning('Unsupported action type received on withdraw endpoint', ['transaction_id' => $transactionId, 'action' => $action]);
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
            'before_balance' => number_format($balance, 2, '.', ''), // Ensure consistent formatting
            'balance' => number_format($balance, 2, '.', ''), // Ensure consistent formatting
            'code' => $code->value,
            'message' => $message,
        ];
    }
}