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
            Log::info('DB transaction started');

            $banker = User::adminUser();
            if (!$banker) {
                Log::error('Banker (system wallet) not found');
                return $this->error('', 'Banker (system wallet) not found', 404);
            }
            Log::info('Banker found', ['banker_id' => $banker->id, 'banker_user_name' => $banker->user_name]);

            $processedPlayers = [];

            foreach ($validated['players'] as $playerData) {
                $player = User::where('user_name', $playerData['player_id'])->first();
                if (!$player) {
                    Log::error('Player not found', ['player_id' => $playerData['player_id']]);
                    throw new \RuntimeException("Player not found: {$playerData['player_id']}");
                }
                Log::info('Player found', ['player_id' => $player->id, 'user_name' => $player->user_name]);

                // Update balances using WalletService
                if ($playerData['win_lose_status'] == 1) {
                    Log::info('Player wins, banker pays player', [
                        'from' => $banker->user_name,
                        'to' => $player->user_name,
                        'amount' => $playerData['amount_changed']
                    ]);
                    $this->walletService->forceTransfer(
                        $banker,
                        $player,
                        $playerData['amount_changed'],
                        TransactionName::Win,
                        ['reason' => 'player_win', 'game_type_id' => $validated['game_type_id']]
                    );
                } else {
                    Log::info('Player loses, player pays banker', [
                        'from' => $player->user_name,
                        'to' => $banker->user_name,
                        'amount' => $playerData['amount_changed']
                    ]);
                    $this->walletService->forceTransfer(
                        $player,
                        $banker,
                        $playerData['amount_changed'],
                        TransactionName::Loss,
                        ['reason' => 'player_lose', 'game_type_id' => $validated['game_type_id']]
                    );
                }

                Log::info('Storing player transaction', [
                    'player_id' => $player->id,
                    'amount_changed' => $playerData['amount_changed'],
                    'win_lose_status' => $playerData['win_lose_status']
                ]);
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
                Log::info('Player balance updated', [
                    'player_id' => $player->id,
                    'current_balance' => $player->balanceFloat
                ]);
                $processedPlayers[] = array_merge($playerData, [
                    'current_balance' => $player->balanceFloat
                ]);
            }

            Log::info('Storing banker transaction');
            // Optionally, store a banker transaction
            ReportTransaction::create([
                'user_id' => $banker->id,
                'game_type_id' => $validated['game_type_id'],
                'transaction_amount' => array_sum(array_column($validated['players'], 'amount_changed')),
                'banker' => 1,
                'final_turn' => 1
            ]);
            $banker->refresh();
            Log::info('Banker balance updated', [
                'banker_id' => $banker->id,
                'current_balance' => $banker->balanceFloat
            ]);

            DB::commit();
            Log::info('DB commit successful');

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