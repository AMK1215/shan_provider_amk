<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use App\Services\ShanApiService;
use App\Services\WalletService;
use App\Models\User;
use App\Models\Admin\ReportTransaction;
use App\Traits\HttpResponses;
use App\Enums\TransactionName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameTransactionController extends Controller
{
    use HttpResponses;

    public function __construct(
        private ShanApiService $shanApiService,
        private WalletService $walletService
    ) {}

    public function processGameTransaction(Request $request)
    {
        try {
            $validated = $request->validate([
                'game_type_id' => 'required|integer',
                'players' => 'required|array|min:1',
                'players.*.player_id' => 'required|string',
                'players.*.bet_amount' => 'required|numeric',
                'players.*.amount_changed' => 'required|numeric',
                'players.*.win_lose_status' => 'required|integer|in:0,1',
            ]);

            Log::info('Processing game transaction', [
                'validated_data' => $validated
            ]);

            DB::beginTransaction();

            $banker = User::adminUser();
            if (!$banker) {
                return $this->error('', 'Banker (system wallet) not found', 404);
            }

            $processedPlayers = [];

            foreach ($validated['players'] as $playerData) {
                $player = User::where('user_name', $playerData['player_id'])->first();
                if (!$player) {
                    throw new \RuntimeException("Player not found: {$playerData['player_id']}");
                }

                // Update balances using WalletService
                if ($playerData['win_lose_status'] == 1) {
                    // Player wins: banker pays the player
                    $this->walletService->forceTransfer(
                        $banker,
                        $player,
                        $playerData['amount_changed'],
                        TransactionName::Win,
                        ['reason' => 'player_win', 'game_type_id' => $validated['game_type_id']]
                    );
                } else {
                    // Player loses: player pays the banker
                    $this->walletService->forceTransfer(
                        $player,
                        $banker,
                        $playerData['amount_changed'],
                        TransactionName::Loss,
                        ['reason' => 'player_lose', 'game_type_id' => $validated['game_type_id']]
                    );
                }

                // Store transaction
                ReportTransaction::create([
                    'user_id' => $player->id,
                    'game_type_id' => $validated['game_type_id'],
                    'transaction_amount' => $playerData['amount_changed'],
                    'status' => $playerData['win_lose_status'],
                    'bet_amount' => $playerData['bet_amount'],
                    'valid_amount' => $playerData['bet_amount'],
                ]);

                $player->refresh();
                $processedPlayers[] = array_merge($playerData, [
                    'current_balance' => $player->balanceFloat
                ]);
            }

            // Optionally, store a banker transaction
            ReportTransaction::create([
                'user_id' => $banker->id,
                'game_type_id' => $validated['game_type_id'],
                'transaction_amount' => array_sum(array_column($validated['players'], 'amount_changed')),
                'banker' => 1,
                'final_turn' => 1
            ]);
            $banker->refresh();

            DB::commit();

            // Call Shan API after local updates
            $transactionData = $this->shanApiService->formatTransactionData(
                $validated['game_type_id'],
                $validated['players']
            );
            $result = $this->shanApiService->processTransaction($transactionData);

            return $this->success([
                'status' => 'success',
                'players' => $processedPlayers,
                'banker' => [
                    'player_id' => $banker->user_name,
                    'balance' => $banker->balanceFloat
                ],
                'provider_result' => $result
            ], 'Transaction processed and stored successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors()
            ]);
            return $this->error('', 'Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('', 'Transaction processing failed: ' . $e->getMessage(), 500);
        }
    }
} 