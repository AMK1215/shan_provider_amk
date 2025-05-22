<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Enums\SeamlessWalletCode;
use App\Models\Transaction as WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionLog;

class WithdrawController extends Controller
{
    public function withdraw(Request $request)
    {
        // Log the incoming request
        Log::info('Withdraw API Request', ['request' => $request->all()]);

        try {
            $request->validate([
                'batch_requests' => 'required|array',
                'operator_code' => 'required|string',
                'currency' => 'required|string',
                'sign' => 'required|string',
                'request_time' => 'required|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Withdraw API Validation Failed', ['errors' => $e->errors()]);
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        // Signature check
        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'withdraw' .
            $secretKey
        );
        $isValidSign = strtolower($request->sign) === strtolower($expectedSign);

        // Allowed currencies
        $allowedCurrencies = ['IDR', 'IDR2', 'KRW2', 'MMK2', 'VND2', 'LAK2', 'KHR2'];
        $isValidCurrency = in_array($request->currency, $allowedCurrencies);

        $results = [];
        foreach ($request->batch_requests as $req) {
            try {
                if (!$isValidSign) {
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => 0.0,
                        'balance' => 0.0,
                        'code' => SeamlessWalletCode::InvalidSignature->value,
                        'message' => 'Invalid signature',
                    ];
                    continue;
                }

                if (!$isValidCurrency) {
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => 0.0,
                        'balance' => 0.0,
                        'code' => SeamlessWalletCode::InternalServerError->value,
                        'message' => 'Invalid Currency',
                    ];
                    continue;
                }

                $user = User::where('user_name', $req['member_account'])->first();
                if (!$user || !$user->wallet) {
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => null,
                        'balance' => null,
                        'code' => SeamlessWalletCode::MemberNotExist->value,
                        'message' => 'Member not found',
                    ];
                    continue;
                }

                $before = $user->wallet->balanceFloat;
                $tx = $req['transactions'][0] ?? null;

                // Check for duplicate transaction by external transaction ID
                $existingTx = WalletTransaction::where('seamless_transaction_id', $tx['id'] ?? null)->first();
                if ($existingTx) {
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => $before,
                        'balance' => $before,
                        'code' => SeamlessWalletCode::DuplicateTransaction->value,
                        'message' => 'Duplicate transaction',
                    ];
                    continue;
                }

                $amount = floatval($tx['amount'] ?? 0);
                if ($amount > $before) {
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => $before,
                        'balance' => $before,
                        'code' => SeamlessWalletCode::InsufficientBalance->value, // 1001
                        'message' => 'Insufficient balance',
                    ];
                    continue;
                }

                DB::beginTransaction();
                $user->wallet->withdrawFloat($amount, [
                    'seamless_transaction_id' => $tx['id'] ?? null,
                    'action' => $tx['action'] ?? null,
                    'wager_code' => $tx['wager_code'] ?? null,
                    'product_code' => $req['product_code'],
                    'game_type' => $req['game_type'] ?? null,
                ]);
                DB::commit();
                $after = $user->wallet->balanceFloat;
                $results[] = [
                    'member_account' => $req['member_account'],
                    'product_code' => $req['product_code'],
                    'before_balance' => $before,
                    'balance' => $after,
                    'code' => SeamlessWalletCode::Success->value,
                    'message' => '',
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Withdraw API Exception', ['error' => $e->getMessage(), 'request' => $req]);
                $results[] = [
                    'member_account' => $req['member_account'],
                    'product_code' => $req['product_code'],
                    'before_balance' => $before ?? null,
                    'balance' => $before ?? null,
                    'code' => SeamlessWalletCode::InternalServerError->value,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Log the transaction details
        TransactionLog::create([
            'type' => 'withdraw',
            'batch_request' => $request->all(),
            'response_data' => $results,
            'status' => 'success',
        ]);

        // Log the response
        Log::info('Withdraw API Response', ['response' => $results]);

        return ApiResponseService::success($results);
    }
}


