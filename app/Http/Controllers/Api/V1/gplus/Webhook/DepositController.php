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
use App\Services\WalletService;
use App\Enums\TransactionName;
use App\Enums\TransactionType;
use App\Models\PlaceBet;

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

        // Signature check
        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'deposit' .
            $secretKey
        );
        $isValidSign = strtolower($request->sign) === strtolower($expectedSign);

        // Allowed currencies
        $allowedCurrencies = ['IDR', 'IDR2', 'KRW2', 'MMK2', 'VND2', 'LAK2', 'KHR2'];
        $isValidCurrency = in_array($request->currency, $allowedCurrencies);

        $results = [];
        $walletService = app(WalletService::class);
        foreach ($request->batch_requests as $req) {
            try {
                Log::debug('Processing batch request', ['req' => $req]);
                if (!$isValidSign) {
                    Log::warning('Invalid signature for member', ['member_account' => $req['member_account'], 'provided' => $request->sign, 'expected' => $expectedSign]);
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
                    Log::warning('Invalid currency for member', ['member_account' => $req['member_account'], 'currency' => $request->currency]);
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
                    Log::warning('Member not found or wallet missing', ['member_account' => $req['member_account']]);
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => 0.0,
                        'balance' => 0.0,
                        'code' => SeamlessWalletCode::MemberNotExist->value,
                        'message' => 'Member not found',
                    ];
                    continue;
                }

                $before = $user->wallet->balanceFloat;
                $tx = $req['transactions'][0] ?? null;
                $action = strtoupper($tx['action'] ?? '');
                Log::debug('Transaction details', ['action' => $action, 'amount' => $tx['amount'] ?? null, 'tx' => $tx]);

                $transactionId = $tx['id'] ?? null;
                $duplicateInPlaceBets = PlaceBet::where('transaction_id', $transactionId)->first();
                $duplicateInTransactions = WalletTransaction::where('seamless_transaction_id', $transactionId)->first();
                if ($duplicateInPlaceBets || $duplicateInTransactions) {
                    Log::warning('Duplicate transaction detected in place_bets or transactions', ['tx_id' => $transactionId]);
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
                if ($amount <= 0) {
                    Log::warning('Deposit with non-positive amount', ['member_account' => $req['member_account'], 'amount' => $amount]);
                    $results[] = [
                        'member_account' => $req['member_account'],
                        'product_code' => $req['product_code'],
                        'before_balance' => $before,
                        'balance' => $before,
                        'code' => SeamlessWalletCode::InsufficientBalance->value, // 1001
                        'message' => 'Deposit amount must be positive',
                    ];
                    continue;
                }

                Log::info('Processing deposit', ['member_account' => $req['member_account'], 'amount' => $amount]);
                DB::beginTransaction();
                $walletService->deposit($user, $amount, TransactionName::Deposit, [
                    'seamless_transaction_id' => $transactionId,
                    'action' => $tx['action'] ?? null,
                    'wager_code' => $tx['wager_code'] ?? null,
                    'product_code' => $req['product_code'],
                    'game_type' => $req['game_type'] ?? null,
                ]);
                // Store all data in place_bets for every transaction (success, fail, or duplicate)
                PlaceBet::updateOrCreate(
                    ['transaction_id' => $tx['id'] ?? ''],
                    [
                        // Batch-level
                        'member_account'    => $req['member_account'],
                        'product_code'      => $req['product_code'],
                        'game_type'         => $req['game_type'] ?? '',
                        'operator_code'     => $request->operator_code,
                        'request_time'      => $request->request_time ? now()->setTimestamp($request->request_time) : null,
                        'sign'              => $request->sign,
                        'currency'          => $request->currency,

                        // Transaction-level
                        'action'            => $tx['action'] ?? '',
                        'amount'            => $tx['amount'] ?? '',
                        'valid_bet_amount'  => $tx['valid_bet_amount'] ?? null,
                        'bet_amount'        => $tx['bet_amount'] ?? null,
                        'prize_amount'      => $tx['prize_amount'] ?? null,
                        'tip_amount'        => $tx['tip_amount'] ?? null,
                        'wager_code'        => $tx['wager_code'] ?? null,
                        'wager_status'      => $tx['wager_status'] ?? null,
                        'round_id'          => $tx['round_id'] ?? null,
                        'payload'           => isset($tx['payload']) ? json_encode($tx['payload']) : null,
                        'settle_at'         => isset($tx['settle_at']) && $tx['settle_at'] ? now()->setTimestamp($tx['settle_at']) : null,
                        'game_code'         => $tx['game_code'] ?? null,
                        'channel_code'      => $tx['channel_code'] ?? null,
                    ]
                );
                DB::commit();
                $after = $user->wallet->balanceFloat;
                Log::info('Deposit successful', ['member_account' => $req['member_account'], 'before' => $before, 'after' => $after]);
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
                    'before_balance' => $before ?? 0.0,
                    'balance' => $before ?? 0.0,
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

        // Log the response
        Log::info('Deposit API Response', ['response' => $results]);

        return ApiResponseService::success($results);
    }
} 