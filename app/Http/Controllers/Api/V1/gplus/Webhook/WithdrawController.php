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
                SeamlessWalletCode::BadRequest,
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

        if (strtolower($request->sign) !== strtolower($expectedSign)) {
            Log::warning('Withdraw Invalid Signature', ['provided' => $request->sign, 'expected' => $expectedSign]);
            return ApiResponseService::error(SeamlessWalletCode::InvalidSignature, 'Invalid signature');
        }

        $responseData = [];

        foreach ($request->batch_requests as $batchRequest) {
            $memberAccount = $batchRequest['member_account'] ?? null;
            $productCode = $batchRequest['product_code'] ?? null;
            $transactions = $batchRequest['transactions'] ?? [];

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
                        'code' => SeamlessWalletCode::BadRequest->value,
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
                    'round_id' => $tx['round_id'] ?? null, // Added round_id to meta
                    'game_code' => $tx['game_code'] ?? null, // Added game_code to meta
                    'channel_code' => $tx['channel_code'] ?? null, // Added channel_code to meta
                    'raw_payload' => $tx,
                ];

                // If only 'BET' actions are expected here, we can simplify this.
                // Assuming 'BET' is the only action type this endpoint handles for deductions.
                if ($action === 'BET') {
                    $betAmount = abs($amount); // Always treat the bet amount as positive for the wallet package

                    if ($betAmount <= 0) {
                        $transactionCode = SeamlessWalletCode::BadRequest->value;
                        $transactionMessage = 'Bet amount must be positive and greater than zero.';
                        Log::warning('Invalid bet amount received', ['transaction_id' => $transactionId, 'amount' => $amount]);
                    } elseif ($user->balanceFloat < $betAmount) {
                        $transactionCode = SeamlessWalletCode::BalanceNotEnough->value;
                        $transactionMessage = 'Insufficient balance';
                        Log::warning('Insufficient balance for bet', ['member_account' => $memberAccount, 'bet_amount' => $betAmount, 'current_balance' => $currentBalance]);
                    } else {
                        try {
                            $this->walletService->withdraw($user, $betAmount, TransactionName::Bet, $meta);
                            $newBalance = $user->balanceFloat;
                            Log::info('Successfully processed bet transaction via WalletService', ['transaction_id' => $transactionId, 'member_account' => $memberAccount, 'bet_amount' => $betAmount, 'new_balance' => $newBalance]);
                        } catch (\Bavix\Wallet\Exceptions\InsufficientFunds $e) {
                             $transactionCode = SeamlessWalletCode::BalanceNotEnough->value;
                             $transactionMessage = 'Insufficient balance (Wallet package)';
                             Log::error('Wallet Insufficient Funds for bet', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                        } catch (\Throwable $e) {
                            $transactionCode = SeamlessWalletCode::ServerError->value;
                            $transactionMessage = 'Failed to process bet transaction: ' . $e->getMessage();
                            Log::error('Error processing bet transaction via WalletService', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
                        }
                    }
                } else {
                    // If any other action type is sent to this endpoint, it's unexpected.
                    $transactionCode = SeamlessWalletCode::BadRequest->value;
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
}