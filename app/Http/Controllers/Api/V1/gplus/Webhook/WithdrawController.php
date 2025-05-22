<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Enums\SeamlessWalletCode;
use App\Models\Transaction; // Assuming you have a Transaction model to record these operations

class WithdrawController extends Controller
{
    public function withdraw(Request $request)
    {
        Log::info('Withdraw API Request', ['request' => $request->all()]);

        try {
            $request->validate([
                'operator_code' => 'required|string',
                'batch_requests' => 'required|array',
                'sign' => 'required|string',
                'request_time' => 'required|integer',
                'currency' => 'required|string', // Based on documentation, currency is a top-level parameter for balance
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Withdraw API Validation Failed', ['errors' => $e->errors()]);
            return ApiResponseService::error(
                SeamlessWalletCode::BadRequest, // Use a more appropriate code for validation failures
                'Validation failed',
                $e->errors()
            );
        }

        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'withdraw' . // The method name for signature generation
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

            $user = User::where('user_name', $memberAccount)->first();

            if (!$user) {
                Log::warning('Member not found for withdraw/bet request', ['member_account' => $memberAccount]);
                $responseData[] = [
                    'member_account' => $memberAccount,
                    'product_code' => $productCode,
                    'before_balance' => number_format(0.00, 2, '.', ''), // Provide a default or actual balance if available
                    'balance' => number_format(0.00, 2, '.', ''),
                    'code' => SeamlessWalletCode::MemberNotExist->value,
                    'message' => 'Member not found',
                ];
                continue; // Continue to the next batch request
            }

            foreach ($transactions as $tx) {
                $transactionId = $tx['id'] ?? null; // Using 'id' as transaction ID
                $action = $tx['action'] ?? null;
                $amount = $tx['amount'] ?? null;

                if (!$transactionId || !$action || $amount === null) {
                    Log::warning('Missing crucial data in transaction for withdraw/bet', ['tx' => $tx]);
                     $responseData[] = [
                        'member_account' => $memberAccount,
                        'product_code' => $productCode,
                        'before_balance' => number_format($user->balance, 2, '.', ''),
                        'balance' => number_format($user->balance, 2, '.', ''),
                        'code' => SeamlessWalletCode::BadRequest->value, // Bad Request for missing data
                        'message' => 'Missing transaction data (id, action, or amount)',
                    ];
                    continue;
                }

                $currentBalance = $user->balance;
                $newBalance = $currentBalance;
                $transactionCode = SeamlessWalletCode::Success->value;
                $transactionMessage = '';

                // Handle the action type
                if ($action === 'BET') {
                    // For a BET action, the 'amount' field in the request (e.g., -10) represents the bet amount.
                    // We need to take its absolute value and ensure it's positive.
                    $betAmount = abs($amount);

                    if ($betAmount <= 0) {
                        $transactionCode = SeamlessWalletCode::BadRequest->value; // Or a more specific code like InvalidAmount
                        $transactionMessage = 'Bet amount must be positive and greater than zero.';
                        Log::warning('Invalid bet amount received', ['transaction_id' => $transactionId, 'amount' => $amount]);
                    } elseif ($currentBalance < $betAmount) {
                        $transactionCode = SeamlessWalletCode::BalanceNotEnough->value;
                        $transactionMessage = 'Insufficient balance';
                        Log::warning('Insufficient balance for bet', ['member_account' => $memberAccount, 'bet_amount' => $betAmount, 'current_balance' => $currentBalance]);
                    } else {
                        $newBalance = $currentBalance - $betAmount;
                        $user->balance = $newBalance;
                        $user->save();

                        // Record the transaction
                        // Assuming your Transaction model has fillable fields like:
                        // user_id, type (e.g., 'bet'), amount, old_balance, new_balance, reference_id (wager_code/transaction_id)
                        Transaction::create([
                            'user_id' => $user->id,
                            'type' => 'bet',
                            'amount' => $betAmount,
                            'old_balance' => $currentBalance,
                            'new_balance' => $newBalance,
                            'reference_id' => $transactionId,
                            'status' => 'completed', // Or 'pending'/'settled' depending on your flow
                            'meta' => $tx, // Store the full transaction payload for debugging
                        ]);
                        Log::info('Successfully processed bet transaction', ['transaction_id' => $transactionId, 'member_account' => $memberAccount, 'bet_amount' => $betAmount, 'new_balance' => $newBalance]);
                    }
                } elseif ($action === 'WITHDRAWAL') { // Assuming you might have a 'WITHDRAWAL' action
                    if ($amount <= 0) {
                        $transactionCode = SeamlessWalletCode::InvalidAmount->value;
                        $transactionMessage = 'Withdrawal amount must be positive.';
                        Log::warning('Invalid withdrawal amount received', ['transaction_id' => $transactionId, 'amount' => $amount]);
                    } elseif ($currentBalance < $amount) {
                        $transactionCode = SeamlessWalletCode::BalanceNotEnough->value;
                        $transactionMessage = 'Insufficient balance for withdrawal';
                        Log::warning('Insufficient balance for withdrawal', ['member_account' => $memberAccount, 'withdrawal_amount' => $amount, 'current_balance' => $currentBalance]);
                    } else {
                        $newBalance = $currentBalance - $amount;
                        $user->balance = $newBalance;
                        $user->save();

                        Transaction::create([
                            'user_id' => $user->id,
                            'type' => 'withdrawal',
                            'amount' => $amount,
                            'old_balance' => $currentBalance,
                            'new_balance' => $newBalance,
                            'reference_id' => $transactionId,
                            'status' => 'completed',
                            'meta' => $tx,
                        ]);
                        Log::info('Successfully processed withdrawal transaction', ['transaction_id' => $transactionId, 'member_account' => $memberAccount, 'withdrawal_amount' => $amount, 'new_balance' => $newBalance]);
                    }
                } else {
                    $transactionCode = SeamlessWalletCode::BadRequest->value;
                    $transactionMessage = 'Unsupported action type';
                    Log::warning('Unsupported action type received', ['transaction_id' => $transactionId, 'action' => $action]);
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
            'message' => 'Processed batch requests', // General success message
            'data' => $responseData, // Return individual transaction results
        ]);
    }
}