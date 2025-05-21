<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Enums\SeamlessWalletCode;
use App\Enums\TransactionType;
use App\Models\Transaction as WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionLog;

class DepositController extends Controller
{
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

        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'deposit' .
            $secretKey
        );
        if (strtolower($request->sign) !== strtolower($expectedSign)) {
            Log::warning('Deposit API Invalid Signature', ['provided' => $request->sign, 'expected' => $expectedSign]);
            return ApiResponseService::error(
                SeamlessWalletCode::InvalidSignature,
                'Invalid signature'
            );
        }

        $results = [];
        foreach ($request->batch_requests as $req) {
            try {
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
                $type = TransactionType::fromAction($tx['action'] ?? '', $amount);
                if ($type !== TransactionType::Deposit) {
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => $before,
                        'balance' => $before,
                        'code' => SeamlessWalletCode::InternalServerError->value,
                        'message' => 'Invalid deposit action',
                    ];
                    continue;
                }

                DB::beginTransaction();
                $user->wallet->depositFloat($amount, [
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
                Log::error('Deposit API Exception', ['error' => $e->getMessage(), 'request' => $req]);
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
            'type' => 'deposit',
            'batch_request' => $request->all(),
            'response_data' => $results,
            'status' => 'success',
        ]);

        Log::info('Deposit API Response', ['response' => $results]);

        return ApiResponseService::success($results);
    }
} 