<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Models\Admin\ReportTransaction;
use App\Models\User;
use App\Services\WalletService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderTransactionCallbackController extends Controller
{
    use HttpResponses;

    public function handle(Request $request, WalletService $walletService)
    {
        // 1. Check Provider Key
        $providedKey = $request->header('X-Provider-Transaction-Key');
        $expectedKey = config('shan_key.transaction_key');
        if ($providedKey !== $expectedKey) {
            Log::warning('Provider callback: Invalid transaction key', ['provided' => $providedKey]);

            return $this->error('', 'Unauthorized. Invalid transaction key.', 401);
        }

        // 2. Validate input
        $validated = $request->validate([
            'player_id' => 'required|string',
            'bet_amount' => 'required|numeric',
            'amount_changed' => 'required|numeric',
            'win_lose_status' => 'required|integer|in:0,1',
            'game_type_id' => 'required|integer',
        ]);

        Log::info('Provider callback: Received transaction', ['data' => $validated]);

        DB::beginTransaction();
        try {
            // 3. Find player (DO NOT auto-create)
            $player = User::where('user_name', $validated['player_id'])->first();
            if (! $player) {
                Log::warning('Provider callback: Player not found', ['player_id' => $validated['player_id']]);

                return $this->error('', 'Player not found', 404);
            }

            // 4. Update balance via WalletService
            if ($validated['win_lose_status'] == 1) {
                $walletService->deposit(
                    $player,
                    $validated['amount_changed'],
                    TransactionName::Win,
                    [
                        'provider_callback' => true,
                        'game_type_id' => $validated['game_type_id'],
                    ]
                );
            } else {
                $walletService->withdraw(
                    $player,
                    $validated['amount_changed'],
                    TransactionName::Loss,
                    [
                        'provider_callback' => true,
                        'game_type_id' => $validated['game_type_id'],
                    ]
                );
            }

            // 5. Store transaction record
            ReportTransaction::create([
                'user_id' => $player->id,
                'game_type_id' => $validated['game_type_id'],
                'transaction_amount' => $validated['amount_changed'],
                'status' => $validated['win_lose_status'],
                'bet_amount' => $validated['bet_amount'],
                'valid_amount' => $validated['bet_amount'],
            ]);

            DB::commit();

            return $this->success([
                'player_id' => $player->user_name,
                'balance' => $player->balanceFloat,
            ], 'Callback processed');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider callback: Exception', ['error' => $e->getMessage()]);

            return $this->error('', 'Failed to process callback', 500);
        }
    }
}
