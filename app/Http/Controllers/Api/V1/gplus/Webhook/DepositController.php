<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Enums\SeamlessWalletCode;
use App\Models\Transaction as WalletTransaction; // Assuming WalletTransaction for main wallet transactions
use Illuminate\Support\Facades\DB;
use App\Models\TransactionLog;
use App\Services\WalletService;
use App\Enums\TransactionName;
use App\Models\PlaceBet;

class DepositController extends Controller
{
    private array $allowedCurrencies = ['IDR', 'IDR2', 'KRW2', 'MMK2', 'VND2', 'LAK2', 'KHR2'];
    private array $withdrawActions = ['BET', 'TIP', 'BET_PRESERVE', 'ROLLBACK', 'CANCEL', 'ADJUSTMENT']; // All possible actions
    private array $depositActions = ['WIN', 'SETTLED', 'JACKPOT', 'BONUS', 'PROMO', 'LEADERBOARD', 'FREEBET', 'PRESERVE_REFUND']; // Actions considered as deposits
    private array $allowedWagerStatuses = ['SETTLED', 'UNSETTLED', 'PENDING', 'CANCELLED']; // Common wager statuses

    public function deposit(Request $request)
    {
        Log::info('Deposit API Request', ['request' => $request->all()]);

        try {
            $request->validate([
                'batch_requests' => 'required|array',
                'operator_code' => 'required|string',
                'currency' => 'required|string',
                'sign' => 'required|string',
                'request_time' => 'required|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Deposit API Validation Failed', ['errors' => $e->errors()]);
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        $results = $this->processTransactions($request, true); // true for deposit

        // Log the transaction details
        TransactionLog::create([
            'type' => 'deposit',
            'batch_request' => $request->all(),
            'response_data' => $results,
            'status' => collect($results)->every(fn($r) => $r['code'] === SeamlessWalletCode::Success->value) ? 'success' : 'partial_success_or_failure',
        ]);

        Log::info('Deposit API Response', ['response' => $results]);

        return ApiResponseService::success($results);
    }

    /**
     * Centralized logic for processing seamless wallet transactions (withdraw/deposit).
     * Maps the Java processTransactions method.
     *
     * @param Request $request
     * @param bool $isDeposit
     * @return array
     */
    private function processTransactions(Request $request, bool $isDeposit): array
    {
        $secretKey = Config::get('seamless_key.secret_key');
        $operatorCode = Config::get('seamless_key.operator_code'); // Use operator code from config

        // Correctly calculate expected sign, ensuring 'deposit'/'withdraw' string is used
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            ($isDeposit ? 'deposit' : 'withdraw') . // This string matters for signature
            $secretKey
        );
        $isValidSign = strtolower($request->sign) === strtolower($expectedSign);
        $isValidCurrency = in_array($request->currency, $this->allowedCurrencies);

        $results = [];
        $walletService = app(WalletService::class);
        $admin = User::adminUser(); // Assuming User::adminUser() exists and returns an admin user for deposits

        foreach ($request->batch_requests as $batchRequest) {
            $memberAccount = $batchRequest['member_account'] ?? null;
            $productCode = $batchRequest['product_code'] ?? null;
            $gameType = $batchRequest['game_type'] ?? '';

            // Handle batch-level errors (if signature/currency are invalid for the whole request)
            if (!$isValidSign) {
                Log::warning('Invalid signature for batch', ['member_account' => $memberAccount, 'provided' => $request->sign, 'expected' => $expectedSign]);
                $results[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::InvalidSignature, 'Invalid signature');
                continue;
            }

            if (!$isValidCurrency) {
                Log::warning('Invalid currency for batch', ['member_account' => $memberAccount, 'currency' => $request->currency]);
                $results[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::InternalServerError, 'Invalid Currency');
                continue;
            }

            try {
                $user = User::where('user_name', $memberAccount)->first();
                if (!$user) {
                    Log::warning('Member not found', ['member_account' => $memberAccount]);
                    $results[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::MemberNotExist, 'Member not found');
                    continue;
                }

                if (!$user->wallet) {
                    Log::warning('Wallet missing for member', ['member_account' => $memberAccount]);
                    $results[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::MemberNotExist, 'Member wallet missing');
                    continue;
                }

                $initialBalance = $user->wallet->balanceFloat; // Get initial balance before any transactions in this batch
                $currentBalance = $initialBalance; // Track current balance within the batch

                foreach ($batchRequest['transactions'] ?? [] as $transactionRequest) {
                    $transactionId = $transactionRequest['id'] ?? null;
                    $action = strtoupper($transactionRequest['action'] ?? '');
                    $wagerCode = $transactionRequest['wager_code'] ?? $transactionRequest['round_id'] ?? null;
                    $amount = floatval($transactionRequest['amount'] ?? 0);

                    // Duplicate check by transaction_id in PlaceBet table
                    $duplicateInPlaceBets = PlaceBet::where('transaction_id', $transactionId)->first();
                    // Also check if the transaction is already recorded in the wallet's internal transactions
                    $duplicateInWalletTransactions = WalletTransaction::whereJsonContains('meta->seamless_transaction_id', $transactionId)->first(); // Use jsonContains for meta
                    
                    if ($duplicateInPlaceBets || $duplicateInWalletTransactions) {
                        Log::warning('Duplicate transaction ID detected in place_bets or wallet_transactions', ['tx_id' => $transactionId, 'member_account' => $memberAccount]);
                        $results[] = $this->buildErrorResponse($memberAccount, $productCode, $currentBalance, SeamlessWalletCode::DuplicateTransaction, 'Duplicate transaction');
                        $this->logPlaceBet($batchRequest, $request, $transactionRequest, 'duplicate', $request->request_time); // Log duplicate attempt
                        continue; // Skip processing this duplicate transaction
                    }

                    // Check for invalid action type or wager status
                    // Only process deposit actions for the deposit endpoint
                    if (!$this->isValidActionForDeposit($action) || !$this->isValidWagerStatus($transactionRequest['wager_status'] ?? null)) {
                        Log::warning('Invalid action or wager status for deposit endpoint', ['action' => $action, 'wager_status' => $transactionRequest['wager_status'] ?? 'N/A', 'member_account' => $memberAccount]);
                        $results[] = $this->buildErrorResponse($memberAccount, $productCode, $currentBalance, SeamlessWalletCode::InvalidAction, 'Invalid action type or wager status for deposit');
                        $this->logPlaceBet($batchRequest, $request, $transactionRequest, 'failed', $request->request_time, 'Invalid action type or wager status for deposit');
                        continue;
                    }

                    // Start a database transaction for each individual transaction request
                    DB::beginTransaction();
                    try {
                        // Re-fetch user and lock wallet inside transaction for isolation
                        $user->refresh(); // Get the latest state of the user and their wallet
                        $user->wallet->lockForUpdate();
                        $beforeTransactionBalance = $user->wallet->balanceFloat;

                        $convertedAmount = $this->toDecimalPlaces($amount * $this->getCurrencyValue($request->currency));

                        // Specific logic for deposit endpoint
                        if ($convertedAmount <= 0) {
                            throw new \Exception('Deposit amount must be positive.');
                        }
                        $walletService->deposit($user, $convertedAmount, TransactionName::Deposit, [
                            'seamless_transaction_id' => $transactionId,
                            'action' => $action,
                            'wager_code' => $wagerCode,
                            'product_code' => $productCode,
                            'game_type' => $gameType,
                            'from_admin' => $admin->id, // If admin is involved in deposits
                        ]);

                        // Get balance after successful transaction
                        $afterTransactionBalance = $user->wallet->balanceFloat;

                        // Log success and add to results
                        Log::info('Transaction successful', ['member_account' => $memberAccount, 'action' => $action, 'before' => $beforeTransactionBalance, 'after' => $afterTransactionBalance]);
                        $results[] = [
                            'member_account' => $memberAccount,
                            'product_code' => $productCode,
                            'before_balance' => $this->toDecimalPlaces($beforeTransactionBalance / $this->getCurrencyValue($request->currency)),
                            'balance' => $this->toDecimalPlaces($afterTransactionBalance / $this->getCurrencyValue($request->currency)),
                            'code' => SeamlessWalletCode::Success->value,
                            'message' => '',
                        ];
                        $currentBalance = $afterTransactionBalance; // Update current balance for next transaction in batch
                        $this->logPlaceBet($batchRequest, $request, $transactionRequest, 'completed', $request->request_time); // Log successful PlaceBet

                        DB::commit(); // Commit inner transaction
                    } catch (\Exception $e) {
                        DB::rollBack(); // Rollback inner transaction
                        Log::error('Transaction processing exception', ['error' => $e->getMessage(), 'member_account' => $memberAccount, 'request_transaction' => $transactionRequest]);
                        $code = SeamlessWalletCode::InternalServerError;
                        if (str_contains($e->getMessage(), 'amount must be positive')) {
                            $code = SeamlessWalletCode::InsufficientBalance; // Re-using 1001 for invalid amount, as per Java's use
                        }
                        $results[] = $this->buildErrorResponse(
                            $memberAccount,
                            $productCode,
                            $this->toDecimalPlaces($currentBalance / $this->getCurrencyValue($request->currency)),
                            $code,
                            $e->getMessage()
                        );
                        $this->logPlaceBet($batchRequest, $request, $transactionRequest, 'failed', $request->request_time, $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                // Catch any unexpected errors that might occur outside the inner transaction loop
                Log::error('Batch processing exception for member', ['member_account' => $memberAccount, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $results[] = $this->buildErrorResponse($memberAccount, $productCode, 0.0, SeamlessWalletCode::InternalServerError, 'An unexpected error occurred during batch processing.');
            }
        }
        return $results;
    }

    /**
     * Helper to build a consistent error response.
     */
    private function buildErrorResponse(string $memberAccount, string $productCode, float $balance, SeamlessWalletCode $code, string $message): array
    {
        return [
            'member_account' => $memberAccount,
            'product_code' => $productCode,
            'before_balance' => $balance,
            'balance' => $balance,
            'code' => $code->value,
            'message' => $message,
        ];
    }

    /**
     * Converts a float to a specified number of decimal places.
     * This emulates Java's `toDecimalPlaces`.
     * You might need a more robust solution for financial calculations.
     *
     * @param float $value
     * @param int $precision
     * @return float
     */
    private function toDecimalPlaces(float $value, int $precision = 4): float
    {
        return round($value, $precision);
    }

    /**
     * Gets the currency conversion value.
     * This is a placeholder; you'd implement actual currency rates here.
     *
     * @param string $currency
     * @return int
     */
    private function getCurrencyValue(string $currency): int
    {
        // Example: If IDR2 means 1 unit = 100 IDR
        return match ($currency) {
            'IDR2' => 100, // Example multiplier
            'KRW2' => 10,
            'MMK2' => 100,
            'VND2' => 1000,
            'LAK2' => 10,
            'KHR2' => 100,
            default => 1, // Default for IDR, etc.
        };
    }

    /**
     * Check if the action is valid specifically for the deposit endpoint.
     *
     * @param string $action
     * @return bool
     */
    private function isValidActionForDeposit(string $action): bool
    {
        return in_array($action, $this->depositActions);
    }

    /**
     * Check if the wager status is valid.
     *
     * @param string|null $wagerStatus
     * @return bool
     */
    private function isValidWagerStatus(?string $wagerStatus): bool
    {
        // If wagerStatus is null, it might be allowed for certain actions or situations
        if (is_null($wagerStatus)) {
            return true; // Or false, depending on your API spec
        }
        return in_array($wagerStatus, $this->allowedWagerStatuses);
    }

    /**
     * Logs the transaction attempt in the place_bets table.
     *
     * @param array $batchRequest
     * @param Request $fullRequest
     * @param array $transactionRequest
     * @param string $status
     * @param int|null $requestTime The original request_time from the full request
     * @param string|null $errorMessage
     * @return void
     */
    private function logPlaceBet(array $batchRequest, Request $fullRequest, array $transactionRequest, string $status, ?int $requestTime, ?string $errorMessage = null): void
    {
        // Convert milliseconds to seconds if necessary
        $requestTimeInSeconds = $requestTime ? floor($requestTime / 1000) : null;
        $settleAtTime = $transactionRequest['settle_at'] ?? $transactionRequest['settled_at'] ?? null;
        $settleAtInSeconds = $settleAtTime ? floor($settleAtTime / 1000) : null;

        PlaceBet::updateOrCreate(
            ['transaction_id' => $transactionRequest['id'] ?? ''], // Use transaction_id for uniqueness
            [
                // Batch-level
                'member_account'    => $batchRequest['member_account'] ?? '',
                'product_code'      => $batchRequest['product_code'] ?? 0,
                'game_type'         => $batchRequest['game_type'] ?? '',
                'operator_code'     => $fullRequest->operator_code,
                // FIX: Convert request_time from milliseconds to seconds if it's in milliseconds
                'request_time'      => $requestTimeInSeconds ? now()->setTimestamp($requestTimeInSeconds) : null,
                'sign'              => $fullRequest->sign,
                'currency'          => $fullRequest->currency,

                // Transaction-level
                'action'            => $transactionRequest['action'] ?? '',
                'amount'            => $transactionRequest['amount'] ?? 0,
                'valid_bet_amount'  => $transactionRequest['valid_bet_amount'] ?? null,
                'bet_amount'        => $transactionRequest['bet_amount'] ?? null,
                'prize_amount'      => $transactionRequest['prize_amount'] ?? null,
                'tip_amount'        => $transactionRequest['tip_amount'] ?? null,
                'wager_code'        => $transactionRequest['wager_code'] ?? null,
                'wager_status'      => $transactionRequest['wager_status'] ?? null,
                'round_id'          => $transactionRequest['round_id'] ?? null,
                'payload'           => isset($transactionRequest['payload']) ? json_encode($transactionRequest['payload']) : null,
                // FIX: Convert settle_at/settled_at from milliseconds to seconds if it's in milliseconds
                'settle_at'         => $settleAtInSeconds ? now()->setTimestamp($settleAtInSeconds) : null,
                'game_code'         => $transactionRequest['game_code'] ?? null,
                'channel_code'      => $transactionRequest['channel_code'] ?? null,
                'status'            => $status,
                // Add error_message column to PlaceBet table if you want to store it
                // 'error_message' => $errorMessage,
            ]
        );
    }
}