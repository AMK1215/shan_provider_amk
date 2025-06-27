<?php

namespace App\Http\Controllers\Api\V1\Shan;

use App\Http\Controllers\Controller;
use App\Models\Admin\ReportTransaction;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShanTransactionController extends Controller
{
    use HttpResponses;

    public function index(Request $request): JsonResponse
    {
        Log::info('ShanTransaction: Received request', [
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'game_type_id' => 'required|integer',
            'players' => 'required|array',
            'players.*.player_id' => 'required|string',
            'players.*.bet_amount' => 'required|numeric',
            'players.*.amount_changed' => 'required|numeric',
            'players.*.win_lose_status' => 'required|integer|in:0,1',
        ]);

        Log::info('ShanTransaction: Request validated successfully', [
            'validated_data' => $validated
        ]);

        try {
            DB::beginTransaction();

            // Process banker
            $banker = User::where('user_name', 'systemWallet')->first();
            if (! $banker) {
                Log::error('ShanTransaction: Banker not found', [
                    'user_name' => 'systemWallet'
                ]);
                return $this->error('', 'Banker (systemWallet) not found', 404);
            }

            Log::info('ShanTransaction: Processing banker transaction', [
                'banker_id' => $banker->id,
                'banker_username' => $banker->user_name
            ]);

            $this->handleBankerTransaction($banker, [
                'amount' => 100, // Adjust as needed
                'is_final_turn' => true,
            ], $validated['game_type_id']);

            $results = [['player_id' => $banker->user_name, 'balance' => $banker->wallet->balance]];

            // Handle player transactions
            foreach ($validated['players'] as $playerData) {
                Log::info('ShanTransaction: Processing player transaction', [
                    'player_data' => $playerData
                ]);

                $player = $this->getUserByUsername($playerData['player_id']);
                if ($player) {
                    $this->handlePlayerTransaction($player, $playerData, $validated['game_type_id']);
                    $results[] = [
                        'player_id' => $player->user_name,
                        'balance'   => $player->wallet->balance,
                    ];
                    Log::info('ShanTransaction: Player transaction completed', [
                        'player_id' => $player->user_name,
                        'new_balance' => $player->wallet->balance
                    ]);
                } else {
                    Log::warning('ShanTransaction: Player not found', [
                        'player_id' => $playerData['player_id']
                    ]);
                }
            }

            DB::commit();
            Log::info('ShanTransaction: All transactions committed successfully', [
                'results' => $results
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ShanTransaction: Transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Transaction failed', $e->getMessage(), 500);
        }

        // --- CALLBACK TO CLIENT DEV SITE FOR EACH PLAYER ---
        $clientTransactionUrl = 'https://luckymillion.pro/api/v1/game/transactions';
        $transactionSecret    = 'yYpfrVcWmkwxWx7um0TErYHj4YcHOOWr'; // Use the agreed secret!

        Log::info('ShanTransaction: Client transaction URL', [
            'client_transaction_url' => $clientTransactionUrl
        ]);

        Log::info('ShanTransaction: Client transaction secret', [
            'client_transaction_secret' => $transactionSecret
        ]);

        // Log::info('ShanTransaction: Client transaction payload', [
        //     'client_transaction_payload' => $validated['players']
        // ]);
        // Log::info('ShanTransaction: Client transaction game type id', [
        //     'client_transaction_game_type_id' => $validated['game_type_id']
        // ]);
        // Log::info('ShanTransaction: Client transaction results', [
        //     'client_transaction_results' => $results
        // ]);
        // Log::info('ShanTransaction: Client transaction banker', [
        //     'client_transaction_banker' => $banker
        // ]);

        foreach ($validated['players'] as $playerData) {
            $clientPayload = [
                'player_id'       => $playerData['player_id'],
                'bet_amount'      => $playerData['bet_amount'],
                'amount_changed'  => $playerData['amount_changed'],
                'win_lose_status' => $playerData['win_lose_status'],
                'game_type_id'    => $validated['game_type_id'],
            ];

            Log::info('ShanTransaction: Sending callback to client', [
                'player_id' => $playerData['player_id'],
                'payload' => $clientPayload
            ]);

            try {
                $clientResp = Http::timeout(5)
                    ->withHeaders([
                        'X-Provider-Transaction-Key' => $transactionSecret,
                        'Accept' => 'application/json',
                    ])
                    ->post($clientTransactionUrl, $clientPayload);

                if ($clientResp->successful()) {
                    Log::info('ShanTransaction: Client callback successful', [
                        'player_id' => $playerData['player_id'],
                        'response' => $clientResp->json()
                    ]);
                } else {
                    Log::warning('ShanTransaction: Client callback failed', [
                        'player_id' => $playerData['player_id'],
                        'status' => $clientResp->status(),
                        'response' => $clientResp->body()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('ShanTransaction: Client callback exception', [
                    'player_id' => $playerData['player_id'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $this->success($results, 'Transaction Successful');
    }

    private function getUserByUsername(string $username): ?User
    {
        return User::where('user_name', $username)->first();
    }

    private function handleBankerTransaction(User $banker, array $bankerData, int $gameTypeId): void
    {
        Log::info('ShanTransaction: Creating banker transaction record', [
            'banker_id' => $banker->id,
            'game_type_id' => $gameTypeId,
            'banker_data' => $bankerData
        ]);

        ReportTransaction::create([
            'user_id' => $banker->id,
            'game_type_id' => $gameTypeId,
            'transaction_amount' => $bankerData['amount'],
            'final_turn' => $bankerData['is_final_turn'] ? 1 : 0,
            'banker' => 1,
        ]);

        if ($bankerData['is_final_turn']) {
            $oldBalance = $banker->wallet->balance;
            $banker->wallet->balance += $bankerData['amount'];
            $banker->wallet->save();
            
            Log::info('ShanTransaction: Updated banker balance', [
                'banker_id' => $banker->id,
                'old_balance' => $oldBalance,
                'new_balance' => $banker->wallet->balance,
                'amount_changed' => $bankerData['amount']
            ]);
        }
    }

    private function handlePlayerTransaction(User $player, array $playerData, int $gameTypeId): void
    {
        Log::info('ShanTransaction: Creating player transaction record', [
            'player_id' => $player->id,
            'game_type_id' => $gameTypeId,
            'player_data' => $playerData
        ]);

        ReportTransaction::create([
            'user_id' => $player->id,
            'game_type_id' => $gameTypeId,
            'transaction_amount' => $playerData['amount_changed'],
            'status' => $playerData['win_lose_status'],
            'bet_amount' => $playerData['bet_amount'],
            'valid_amount' => $playerData['bet_amount'],
        ]);

        $this->updatePlayerBalance($player, $playerData['amount_changed'], $playerData['win_lose_status']);
    }

    private function updatePlayerBalance(User $player, float $amountChanged, int $winLoseStatus): void
    {
        $oldBalance = $player->wallet->balance;
        
        if ($winLoseStatus === 1) {
            $player->wallet->balance += $amountChanged;
        } else {
            $player->wallet->balance -= $amountChanged;
        }
        $player->wallet->save();

        Log::info('ShanTransaction: Updated player balance', [
            'player_id' => $player->id,
            'old_balance' => $oldBalance,
            'new_balance' => $player->wallet->balance,
            'amount_changed' => $amountChanged,
            'win_lose_status' => $winLoseStatus
        ]);
    }
}
