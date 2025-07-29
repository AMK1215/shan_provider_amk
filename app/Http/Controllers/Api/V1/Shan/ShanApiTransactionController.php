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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use DateTimeImmutable;
use DateTimeZone;


class ShanApiTransactionController extends Controller
{
    use HttpResponses;

    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function ShanTransactionCreate(Request $request): JsonResponse
    {
        Log::info('ShanTransaction: Request received', [
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'banker' => 'required|array',
            'banker.player_id' => 'required|string', // This will be the ID of the banker (SYS001 or a player ID)
            'players' => 'required|array',
            'players.*.player_id' => 'required|string',
            'players.*.bet_amount' => 'required|numeric|min:0',
            'players.*.win_lose_status' => 'required|integer|in:0,1',
            //'game_type_id' => 'required|integer',
        ]);

        // --- Agent Secret Key Retrieval ---
        // Get the player_id of one of the players (not the banker) to find the agent
        // Assuming all players belong to the same agent in a given game round.
        // If the 'players' array could be empty (e.g., only banker involved in some edge case),
        // you'd need a different way to get the agent_code, perhaps from the banker or a direct request parameter.
        // For now, assuming 'players' will always have at least one non-banker player.
        $samplePlayerId = collect($validated['players'])
                            ->firstWhere('player_id', '!=', $validated['banker']['player_id'])['player_id'] ?? null;

        Log::info('Sample Player ID', ['samplePlayerId' => $samplePlayerId]);

        // If no non-banker player is found, fall back to the banker's agent code if the banker is also a player
        if (!$samplePlayerId) {
            $samplePlayerId = $validated['banker']['player_id'];
        }

        $player = User::where('user_name', $samplePlayerId)->first();

        if (!$player) {
            return $this->error('Player not found to determine agent', 'Player not found', 404);
        }

        $player_agent_code = $player->shan_agent_code;
        $agent = User::where('shan_agent_code', $player_agent_code)->first();

        Log::info('Player Agent Code', ['player_agent_code' => $player_agent_code]);

        // if (!$agent) {
        //     return $this->error('Agent not found for player\'s agent code', 'Agent not found', 404);
        // }

        $secret_key = $agent->shan_secret_key;
        $callback_url_base = $agent->shan_callback_url;

        // if (!$secret_key) {
        //     return $this->error('Secret Key not set for agent', 'Secret Key not set', 404);
        // }
        // if (!$callback_url_base) {
        //     return $this->error('Callback URL not set for agent', 'Callback URL not set', 404);
        // }

        Log::info('Agent Secret Key Retrieved', [
            'agent_username' => $agent->user_name,
            'secret_key_masked' => Str::mask($secret_key, '*', 0, max(0, strlen($secret_key) - 4)),
            'callback_url_base' => $callback_url_base,
        ]);

        $agent_balance = $agent->wallet->balanceFloat;
        // if ($agent_balance < 0) {
        //     return $this->error('Agent balance is negative', 'Agent balance is negative', 404);
        // }

        Log::info('Agent balance', ['agent_balance' => $agent_balance]);

        do {
            $wager_code = Str::random(32);
        } while (ReportTransaction::where('wager_code', $wager_code)->exists());

        $results = []; // For the API response back to the game client (all participants)
        $callbackPlayers = []; // For the callback payload (ONLY actual players, excluding banker)
        $totalPlayerNet = 0;

        try {
            DB::beginTransaction();

            // Process all participants (players and banker if they are also in 'players' array)
            // The incoming 'players' array contains all non-banker participants
            // and potentially the banker if the banker is also a player.
            foreach ($validated['players'] as $playerData) {
                $participant = User::where('user_name', $playerData['player_id'])->first();
                if (!$participant) {
                    throw new \RuntimeException("Participant not found: {$playerData['player_id']}. Transaction aborted.");
                }

                $oldBalance = $participant->wallet->balanceFloat;
                $betAmount = $playerData['bet_amount'];
                $winLose = $playerData['win_lose_status'];

                $amountChanged = ($winLose == 1) ? $betAmount : -$betAmount;
                $totalPlayerNet += $amountChanged; // This accumulates net for all participants in 'players' array

                if ($amountChanged > 0) {
                    $this->walletService->deposit($participant, $amountChanged, TransactionName::GameWin, [
                        'description' => 'Win from Shan game', 'wager_code' => $wager_code, 'bet_amount' => $betAmount,
                    ]);
                } elseif ($amountChanged < 0) {
                    $this->walletService->withdraw($participant, abs($amountChanged), TransactionName::GameLoss, [
                        'description' => 'Loss in Shan game', 'wager_code' => $wager_code, 'bet_amount' => $betAmount,
                    ]);
                }

                $participant->refresh();

                ReportTransaction::create([
                    'user_id' => $participant->id, 'agent_id' => $participant->agent_id, 'member_account' => $participant->user_name,
                    'transaction_amount' => abs($amountChanged), 'status' => $winLose, 'bet_amount' => $betAmount,
                    'valid_amount' => $betAmount, 'before_balance' => $oldBalance, 'after_balance' => $participant->wallet->balanceFloat,
                    'banker' => ($participant->user_name === $validated['banker']['player_id']) ? 1 : 0, // Mark as banker if this participant is the banker
                    'wager_code' => $wager_code, 'settled_status' => $winLose == 1 ? 'settled_win' : 'settled_loss',
                ]);

                // Add to results for the API response back to the game client (all participants)
                $results[] = [
                    'player_id' => $participant->user_name,
                    'balance' => $participant->wallet->balanceFloat,
                ];

                // Add to callbackPlayers ONLY if this participant is NOT the banker
                if ($participant->user_name !== $validated['banker']['player_id']) {
                    $callbackPlayers[] = [
                        'player_id' => $participant->user_name,
                        'balance' => $participant->wallet->balanceFloat,
                    ];
                }
            }

            // BANKER: Handle the explicit banker entry (whether system or player)
            $bankerUserName = $validated['banker']['player_id'];
            $banker = User::where('user_name', $bankerUserName)->first();

            // If the banker is SYS001, it won't be in $validated['players'] array, so it needs separate processing.
            // If the banker is a player, they were already processed in the loop above.
            // We need to ensure the banker's balance change is correctly calculated and applied.

            // The $totalPlayerNet already includes the net change for the player acting as banker
            // if they were part of the $validated['players'] array.
            // If the banker is SYS001, then $totalPlayerNet only represents the actual players.
            // This logic needs to be robust for both cases.

            // Let's refine the banker logic to handle SYS001 vs Player-Banker cleanly.
            // We need to calculate the *true* total net of the non-banker players.
            $trueTotalPlayerNet = 0;
            foreach ($validated['players'] as $playerData) {
                if ($playerData['player_id'] !== $bankerUserName) {
                    $trueTotalPlayerNet += ($playerData['win_lose_status'] == 1) ? $playerData['bet_amount'] : -$playerData['bet_amount'];
                }
            }

            // The banker's change is the inverse of the true total player net
            $bankerAmountChange = -$trueTotalPlayerNet;

            if ($bankerAmountChange > 0) {
                $this->walletService->deposit($banker, $bankerAmountChange, TransactionName::BankerDeposit, [
                    'description' => 'Banker receive (from non-banker players)', 'wager_code' => $wager_code
                ]);
            } elseif ($bankerAmountChange < 0) {
                $this->walletService->withdraw($banker, abs($bankerAmountChange), TransactionName::BankerWithdraw, [
                    'description' => 'Banker payout (to non-banker players)', 'wager_code' => $wager_code
                ]);
            }

            $banker->refresh();

            // Ensure banker transaction is recorded if not already (e.g., if SYS001)
            // If the banker was a player and processed in the loop, this would be a duplicate record.
            // We need to check if a ReportTransaction for this banker and wager_code already exists.
            $existingBankerReport = ReportTransaction::where('user_id', $banker->id)
                                                    ->where('wager_code', $wager_code)
                                                    ->where('banker', 1)
                                                    ->first();
            if (!$existingBankerReport) {
                ReportTransaction::create([
                    'user_id' => $banker->id, 'agent_id' => $banker->agent_id ?? null, 'member_account' => $banker->user_name,
                    'transaction_amount' => abs($bankerAmountChange), 'before_balance' => $bankerOldBalance,
                    'after_balance' => $banker->wallet->balanceFloat, 'banker' => 1,
                    'status' => $bankerAmountChange >= 0 ? 1 : 0, 'wager_code' => $wager_code,
                    'settled_status' => $bankerAmountChange >= 0 ? 'settled_win' : 'settled_loss',
                ]);
            } else {
                // Update existing banker report if needed (e.g., if amount changed)
                // For simplicity, we'll just log that it was already handled.
                Log::info('ShanTransaction: Banker transaction already recorded in loop.', [
                    'banker_id' => $banker->user_name, 'wager_code' => $wager_code
                ]);
            }


            // Add banker to results for the API response back to the game client
            // Ensure this is only added once, or update if already present from 'players' loop
            $bankerResultExists = false;
            foreach ($results as &$res) { // Use reference to modify array in place
                if ($res['player_id'] === $banker->user_name) {
                    $res['balance'] = $banker->wallet->balanceFloat; // Update balance if already there
                    $bankerResultExists = true;
                    break;
                }
            }
            if (!$bankerResultExists) {
                $results[] = [
                    'player_id' => $banker->user_name,
                    'balance' => $banker->wallet->balanceFloat,
                ];
            }


            DB::commit();

            $callback_url = $callback_url_base . 'https://ponewine20x.xyz/api/shan/client/balance-update';

            $callbackPayload = [
                'wager_code' => $wager_code,
                'game_type_id' => 15,
                'players' => $callbackPlayers, // Use the new array that only contains actual players
                'banker_balance' => $banker->wallet->balanceFloat, // This is the banker's final balance
                'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeImmutable::ISO8601),
                'total_player_net' => $trueTotalPlayerNet, // Use true net for non-banker players
                'banker_amount_change' => $bankerAmountChange,
            ];

            // ksort($callbackPayload);
            // $signature = hash_hmac('md5', json_encode($callbackPayload), $secret_key);
            // $callbackPayload['signature'] = $signature;

            try {
                $client = new Client();
                $response = $client->post($callback_url, [
                    'json' => $callbackPayload,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 10,
                    'connect_timeout' => 5,
                ]);

                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody()->getContents();

                if ($statusCode >= 200 && $statusCode < 300) {
                    Log::info('ShanTransaction: Callback to client site successful', [
                        'callback_url' => $callback_url, 'status_code' => $statusCode,
                        'response_body' => $responseBody, 'wager_code' => $wager_code,
                    ]);
                } else {
                    Log::error('ShanTransaction: Callback to client site failed with non-2xx status', [
                        'callback_url' => $callback_url, 'status_code' => $statusCode,
                        'response_body' => $responseBody, 'payload' => $callbackPayload,
                        'wager_code' => $wager_code,
                    ]);
                    // TODO: Implement a robust retry mechanism
                }

            } catch (RequestException $e) {
                Log::error('ShanTransaction: Callback to client site failed (Guzzle RequestException)', [
                    'callback_url' => $callback_url, 'error' => $e->getMessage(),
                    'payload' => $callbackPayload, 'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response',
                    'wager_code' => $wager_code,
                ]);
                // TODO: Implement a robust retry mechanism
            } catch (\Exception $e) {
                Log::error('ShanTransaction: Callback to client site failed (General Exception)', [
                    'callback_url' => $callback_url, 'error' => $e->getMessage(),
                    'payload' => $callbackPayload, 'wager_code' => $wager_code,
                ]);
                // TODO: Implement a robust retry mechanism
            }

            Log::info('ShanTransaction: Transaction completed successfully (including callback attempt)', [
                'wager_code' => $wager_code, 'total_player_net' => $trueTotalPlayerNet, // Log true player net
                'banker_amount_change' => $bankerAmountChange, 'system_wallet_balance' => $banker->wallet->balanceFloat,
                'results' => $results,
            ]);

            return $this->success($results, 'Transaction Successful');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ShanTransaction: Transaction failed', [
                'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('Transaction failed', $e->getMessage(), 500);
        }
    }
}


