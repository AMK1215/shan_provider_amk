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
use App\Enums\TransactionType;
use App\Enums\TransactionName;
use App\Models\PlaceBet;

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
        $walletService = app(WalletService::class);
        $admin = User::adminUser();
        $allowedActions = [
            'BET', 'WIN', 'ROLLBACK', 'CANCEL', 'ADJUSTMENT', 'SETTLED', 'JACKPOT', 'BONUS', 'PROMO', 'LEADERBOARD'
        ];
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
                $transactions = $req['transactions'] ?? [];
                foreach ($transactions as $tx) {
                    $transactionId = $tx['id'] ?? null;
                    $wagerCode = $tx['wager_code'] ?? $tx['round_id'] ?? null;
                    $action = strtoupper($tx['action'] ?? '');

                    // Duplicate check by wager_code or round_id + action
                    $duplicate = PlaceBet::where(function($query) use ($wagerCode) {
                            $query->where('wager_code', $wagerCode)
                                  ->orWhere('round_id', $wagerCode);
                        })
                        ->where('action', $action)
                        ->first();
                    if ($duplicate) {
                        Log::warning('Duplicate transaction detected in place_bets by wager_code/round_id + action', ['wager_code' => $wagerCode, 'action' => $action]);
                        $results[] = [
                            'member_account' => $req['member_account'],
                            'product_code' => $req['product_code'],
                            'before_balance' => $before,
                            'balance' => $before,
                            'code' => SeamlessWalletCode::DuplicateTransaction->value,
                            'message' => 'Duplicate transaction',
                        ];
                        // Store the duplicate attempt as well
                        PlaceBet::updateOrCreate(
                            ['transaction_id' => $transactionId],
                            [
                                'member_account'    => $req['member_account'],
                                'product_code'      => $req['product_code'],
                                'game_type'         => $req['game_type'] ?? '',
                                'operator_code'     => $request->operator_code,
                                'request_time'      => $request->request_time ? now()->setTimestamp($request->request_time) : null,
                                'sign'              => $request->sign,
                                'currency'          => $request->currency,
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
                                'status'            => 'duplicate',
                            ]
                        );
                        continue;
                    }
                    Log::debug('Transaction details', ['action' => $action, 'amount' => $tx['amount'] ?? null, 'tx' => $tx]);

                    if (!in_array($action, $allowedActions)) {
                        Log::warning('Invalid action', ['action' => $action, 'member_account' => $req['member_account']]);
                        $results[] = [
                            'member_account' => $req['member_account'],
                            'product_code' => $req['product_code'],
                            'before_balance' => $before,
                            'balance' => $before,
                            'code' => SeamlessWalletCode::BetNotExist->value,
                            'message' => 'Invalid action',
                        ];
                        continue;
                    }

                    $amount = floatval($tx['amount'] ?? 0);
                    $withdrawActions = ['BET', 'TIP', 'BET_PRESERVE'];
                    $depositActions = ['SETTLED', 'JACKPOT', 'BONUS', 'PROMO', 'LEADERBOARD', 'FREEBET', 'PRESERVE_REFUND'];

                    if (in_array($action, $withdrawActions)) {
                        if ($amount <= 0) {
                            Log::warning('Withdraw action with non-positive amount', ['member_account' => $req['member_account'], 'action' => $action, 'amount' => $amount]);
                            $results[] = [
                                'member_account' => $req['member_account'],
                                'product_code' => $req['product_code'],
                                'before_balance' => $before,
                                'balance' => $before,
                                'code' => SeamlessWalletCode::InsufficientBalance->value, // 1001
                                'message' => 'Withdraw amount must be positive',
                            ];
                            continue;
                        }
                        if ($amount > $before) {
                            Log::warning('Insufficient balance', ['member_account' => $req['member_account'], 'amount' => $amount, 'before_balance' => $before]);
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
                        Log::info('Processing withdraw', ['member_account' => $req['member_account'], 'amount' => $amount]);
                        DB::beginTransaction();
                        $walletService->withdraw($user, $amount, TransactionName::Withdraw, [
                            'seamless_transaction_id' => $transactionId,
                            'action' => $tx['action'] ?? null,
                            'wager_code' => $tx['wager_code'] ?? null,
                            'product_code' => $req['product_code'],
                            'game_type' => $req['game_type'] ?? null,
                        ]);
                        // Store in place_bets for audit/duplicate check
                        PlaceBet::updateOrCreate(
                            ['transaction_id' => $transactionId],
                            [
                                'member_account'    => $req['member_account'],
                                'product_code'      => $req['product_code'],
                                'game_type'         => $req['game_type'] ?? '',
                                'operator_code'     => $request->operator_code,
                                'request_time'      => $request->request_time ? now()->setTimestamp($request->request_time) : null,
                                'sign'              => $request->sign,
                                'currency'          => $request->currency,
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
                                'status'            => 'completed',
                            ]
                        );
                        DB::commit();
                        $after = $user->wallet->balanceFloat;
                        Log::info('Withdraw successful', ['member_account' => $req['member_account'], 'before' => $before, 'after' => $after]);
                        $results[] = [
                            'member_account' => $req['member_account'],
                            'product_code' => $req['product_code'],
                            'before_balance' => $before,
                            'balance' => $after,
                            'code' => SeamlessWalletCode::Success->value,
                            'message' => '',
                        ];
                        $before = $after;
                    } elseif (in_array($action, $depositActions)) {
                        if ($amount <= 0) {
                            Log::warning('Deposit action with non-positive amount', ['member_account' => $req['member_account'], 'action' => $action, 'amount' => $amount]);
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
                            'from_admin' => $admin->id,
                        ]);
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
                        $before = $after;
                    } else {
                        Log::warning('Unknown or unsupported action', ['member_account' => $req['member_account'], 'action' => $action]);
                        $results[] = [
                            'member_account' => $req['member_account'],
                            'product_code' => $req['product_code'],
                            'before_balance' => $before,
                            'balance' => $before,
                            'code' => SeamlessWalletCode::InvalidAction->value,
                            'message' => 'Invalid or unsupported action',
                        ];
                        continue;
                    }

                    // After each result (success or fail), store the transaction in place_bets if not already stored
                    PlaceBet::updateOrCreate(
                        ['transaction_id' => $transactionId],
                        [
                            'member_account'    => $req['member_account'],
                            'product_code'      => $req['product_code'],
                            'game_type'         => $req['game_type'] ?? '',
                            'operator_code'     => $request->operator_code,
                            'request_time'      => $request->request_time ? now()->setTimestamp($request->request_time) : null,
                            'sign'              => $request->sign,
                            'currency'          => $request->currency,
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
                            'status'            => isset($results[count($results)-1]['code']) && $results[count($results)-1]['code'] === SeamlessWalletCode::Success->value ? 'completed' : (isset($results[count($results)-1]['code']) && $results[count($results)-1]['code'] === SeamlessWalletCode::DuplicateTransaction->value ? 'duplicate' : 'failed'),
                        ]
                    );
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Withdraw API Exception', ['error' => $e->getMessage(), 'request' => $req]);
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


