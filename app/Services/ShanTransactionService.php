<?php

namespace App\Services;

use App\Models\Admin\ReportTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Enums\TransactionName;

class ShanTransactionService
{
    private const PROVIDER_URL = 'https://ponewine20x.xyz/api/transactions';
    private const TRANSACTION_KEY = 'yYpfrVcWmkwxWx7um0TErYHj4YcHOOWr';

    public function __construct(
        private WalletService $walletService
    ) {}

    public function processTransaction(array $validated, array $players): array
    {
        $admin = User::adminUser();
        if (!$admin) {
            throw new \RuntimeException('Admin (system wallet) not found');
        }

        DB::beginTransaction();
        try {
            $processedPlayers = [];
            foreach ($players as $playerData) {
                $user = User::where('user_name', $playerData['player_id'])->first();
                if (!$user) {
                    throw new \RuntimeException("Player not found: {$playerData['player_id']}");
                }

                $this->handleWalletTransaction($playerData, $user, $admin);
                $this->storeTransactionHistory($playerData, $user);
                
                // Refresh user balance
                $user->refresh();
                
                // Add processed player with updated balance
                $processedPlayers[] = array_merge($playerData, [
                    'current_balance' => $user->balanceFloat
                ]);
            }
            
            DB::commit();
            
            return $this->notifyProvider($validated, $processedPlayers);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process Shan transaction', [
                'error' => $e->getMessage(),
                'players' => $players,
                'data' => $validated
            ]);
            throw $e;
        }
    }

    private function handleWalletTransaction(array $playerData, User $user, User $admin): void
    {
        if ($playerData['win_lose_status'] == 1) {
            $this->walletService->forceTransfer(
                $admin, 
                $user, 
                $playerData['amount_changed'], 
                TransactionName::Win, 
                ['reason' => 'player_win', 'game_type_id' => $playerData['game_type_id']]
            );
        } else {
            $this->walletService->forceTransfer(
                $user, 
                $admin, 
                $playerData['amount_changed'], 
                TransactionName::Loss, 
                ['reason' => 'player_lose', 'game_type_id' => $playerData['game_type_id']]
            );
        }
    }

    private function storeTransactionHistory(array $playerData, User $user): void
    {
        ReportTransaction::create([
            'game_type_id' => $playerData['game_type_id'],
            'user_id' => $user->id,
            'transaction_amount' => $playerData['amount_changed'],
            'status' => $playerData['win_lose_status'],
            'bet_amount' => $playerData['bet_amount'],
            'valid_amount' => $playerData['bet_amount'],
        ]);
    }

    private function notifyProvider(array $validated, array $players): array
    {
        $payload = [
            'game_type_id' => $validated['game_type_id'],
            'players' => $players
        ];

        $response = Http::withHeaders([
            'X-Transaction-Key' => self::TRANSACTION_KEY,
            'Accept' => 'application/json',
        ])->post(self::PROVIDER_URL, $payload);

        if (!$response->successful()) {
            Log::error('Provider transaction failed', [
                'payload' => $payload,
                'response' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to report to provider');
        }

        return [
            'status' => 'success',
            'provider_result' => $response->json(),
            'players' => $players // Include updated player data with balances
        ];
    }
} 