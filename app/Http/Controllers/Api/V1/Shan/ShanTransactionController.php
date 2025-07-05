<?php

namespace App\Http\Controllers\Api\V1\Shan;

use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Models\Admin\ReportTransaction;
use App\Models\User;
use App\Services\WalletService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShanTransactionController extends Controller
{
    use HttpResponses;

    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function ShanTransactionCreate(Request $request): JsonResponse
    {
        // Step 1: Validate
        $validated = $request->validate([
            'banker' => 'required|array',
            'banker.player_id' => 'required|string',
            'banker.amount' => 'required|numeric',
            'players' => 'required|array',
            'players.*.player_id' => 'required|string',
            'players.*.bet_amount' => 'required|numeric|min:0',
            'players.*.win_lose_status' => 'required|integer|in:0,1'
           // 'wager_code' => 'required|string|max:50' // unique idempotency key
        ]);

        $wager_code = Str::random(10);

        // Step 2: Idempotency check (for both banker & players)
        if (
            ReportTransaction::where('wager_code', $wager_code)->exists()
        ) {
            return $this->error('Duplicate transaction!', 'This round already settled.', 409);
        }

        $results = [];

        try {
            DB::beginTransaction();

            // BANKER
            $banker = User::where('user_name', $validated['banker']['player_id'])->firstOrFail();
            $bankerOldBalance = $banker->wallet->balanceFloat;
            $bankerAmountChange = $validated['banker']['amount'];

            // amount_changed = all player payout - all player bet (or your logic)
            if ($bankerAmountChange > 0) {
                $this->walletService->deposit(
                    $banker,
                    $bankerAmountChange,
                    TransactionName::BankerDeposit,
                    [
                        'description' => 'Banker receive',
                        'wager_code' => $wager_code
                    ]
                );
            } elseif ($bankerAmountChange < 0) {
                $this->walletService->withdraw(
                    $banker,
                    abs($bankerAmountChange),
                    TransactionName::BankerWithdraw,
                    [
                        'description' => 'Banker payout',
                        'wager_code' => $wager_code
                    ]
                );
            }
            $banker->refresh();

            ReportTransaction::create([
                'user_id' => $banker->id,
                'agent_id' => $banker->agent_id ?? null,
                'member_account' => $banker->user_name,
                'transaction_amount' => abs($bankerAmountChange),
                'before_balance' => $bankerOldBalance,
                'after_balance' => $banker->wallet->balanceFloat,
                'banker' => 1,
                'status' => $bankerAmountChange >= 0 ? 1 : 0,
                'wager_code' => $wager_code,
                'settled_status' => $bankerAmountChange >= 0 ? 'settled_win' : 'settled_loss',
            ]);

            $results[] = [
                'player_id' => $banker->user_name,
                'balance' => $banker->wallet->balanceFloat,
            ];

            // PLAYERS
            foreach ($validated['players'] as $playerData) {
                $player = User::where('user_name', $playerData['player_id'])->first();
                if (!$player) continue;

                $oldBalance = $player->wallet->balanceFloat;
                $betAmount = $playerData['bet_amount'];
                $winLose = $playerData['win_lose_status'];

                // amount_changed = betAmount or -betAmount
                $amountChanged = ($winLose == 1) ? $betAmount : -$betAmount;

                // Wallet
                if ($amountChanged > 0) {
                    $this->walletService->deposit(
                        $player,
                        $amountChanged,
                        TransactionName::GameWin,
                        [
                            'description' => 'Win from Shan game',
                            'wager_code' => $wager_code,
                            'bet_amount' => $betAmount,
                        ]
                    );
                } elseif ($amountChanged < 0) {
                    $this->walletService->withdraw(
                        $player,
                        abs($amountChanged),
                        TransactionName::GameLoss,
                        [
                            'description' => 'Loss in Shan game',
                            'wager_code' => $wager_code,
                            'bet_amount' => $betAmount,
                        ]
                    );
                }

                $player->refresh();

                // DB
                ReportTransaction::create([
                    'user_id' => $player->id,
                    'agent_id' => $player->agent_id,
                    'member_account' => $player->user_name,
                    'transaction_amount' => abs($amountChanged),
                    'status' => $winLose,
                    'bet_amount' => $betAmount,
                    'valid_amount' => $betAmount,
                    'before_balance' => $oldBalance,
                    'after_balance' => $player->wallet->balanceFloat,
                    'banker' => 0,
                    'wager_code' => $wager_code,
                    'settled_status' => $winLose == 1 ? 'settled_win' : 'settled_loss',
                ]);

                $results[] = [
                    'player_id' => $player->user_name,
                    'balance' => $player->wallet->balanceFloat,
                ];
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ShanTransaction: Transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('Transaction failed', $e->getMessage(), 500);
        }

        return $this->success($results, 'Transaction Successful');
    }
}
