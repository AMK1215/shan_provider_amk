<?php

namespace App\Http\Controllers\Api\V1\Shan;

use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Models\Admin\ReportTransaction;
use App\Models\User;
use App\Services\WalletService;
use App\Traits\HttpResponses;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; // Import RequestException for specific error handling
use Illuminate\Support\Facades\Log; // <--- ADD THIS LINE
use Illuminate\Support\Str;     // <--- ADD THIS LINE FOR CONSISTENCY WITH UTC


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
        Log::info('ShanTransaction: Request received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            // Step 1: Enhanced Validation with game_type_id
            $validated = $request->validate([
                'banker' => 'nullable|array',
                'banker.player_id' => 'nullable|string',
                'players' => 'required|array|min:1',
                'players.*.player_id' => 'required|string',
                'players.*.bet_amount' => 'required|numeric|min:0',
                'players.*.win_lose_status' => 'required|integer|in:0,1',
                'players.*.amount_changed' => 'required|numeric',
            ]);

            // Set game_type_id to 15 (always for Shan games)
            $gameTypeId = 15;

            Log::info('ShanTransaction: Validation passed', [
                'game_type_id' => $gameTypeId,
                'player_count' => count($validated['players']),
                'has_banker_data' => isset($validated['banker']),
                'banker_player_id' => $validated['banker']['player_id'] ?? 'not_set',
                'all_player_ids' => array_column($validated['players'], 'player_id'),
            ]);

            // Step 2: Check for duplicate transaction using wager_code
            $wagerCode = $request->header('X-Wager-Code');
            if ($wagerCode && ReportTransaction::where('wager_code', $wagerCode)->exists()) {
                Log::warning('ShanTransaction: Duplicate transaction detected', [
                    'wager_code' => $wagerCode,
                ]);
                return $this->error('Duplicate transaction', 'This transaction has already been processed', 409);
            }

            // Step 3: Get first player for agent lookup
            $firstPlayerId = $validated['players'][0]['player_id'];
            
            // Try to find as player first, then as agent if not found
            $firstPlayer = User::where('user_name', $firstPlayerId)->first();
            
            if (!$firstPlayer) {
                Log::warning('ShanTransaction: Player not found, checking if it\'s an agent', [
                    'player_id' => $firstPlayerId,
                ]);
                
                // Check if this is actually an agent ID being passed as player
                $possibleAgent = User::where('user_name', $firstPlayerId)
                                   ->whereIn('type', [20, 10]) // Agent or Owner types
                                   ->first();
                
                if ($possibleAgent) {
                    Log::warning('ShanTransaction: First "player" is actually an agent, skipping to find real player', [
                        'agent_like_player_id' => $firstPlayerId,
                        'agent_type' => $possibleAgent->type,
                    ]);
                    
                    // Find a real player from the remaining players
                    $realPlayer = null;
                    foreach ($validated['players'] as $playerData) {
                        if ($playerData['player_id'] !== $firstPlayerId) {
                            $realPlayer = User::where('user_name', $playerData['player_id'])
                                            ->where('type', 40) // Player type
                                            ->first();
                            if ($realPlayer) {
                                break;
                            }
                        }
                    }
                    
                    if ($realPlayer) {
                        $firstPlayer = $realPlayer;
                        Log::info('ShanTransaction: Found real player for agent lookup', [
                            'original_first_player' => $firstPlayerId,
                            'real_player_id' => $firstPlayer->user_name,
                            'real_player_db_id' => $firstPlayer->id,
                        ]);
                    } else {
                        Log::error('ShanTransaction: No real players found in transaction');
                        return $this->error('No players found', 'No valid players found in transaction', 404);
                    }
                } else {
                    Log::error('ShanTransaction: First player not found', [
                        'player_id' => $firstPlayerId,
                    ]);
                    return $this->error('Player not found', 'First player not found in system', 404);
                }
            }

            // Step 4: Get agent information and system wallet
            $agent = null;
            
            // Check if banker information can help us find the agent
            $bankerPlayerId = $validated['banker']['player_id'] ?? null;
            if ($bankerPlayerId) {
                $bankerUser = User::where('user_name', $bankerPlayerId)->first();
                if ($bankerUser && in_array($bankerUser->type, [10, 20])) {
                    // The banker is an agent - use this as our agent
                    $agent = $bankerUser;
                    Log::info('ShanTransaction: Found agent from banker data', [
                        'banker_player_id' => $bankerPlayerId,
                        'agent_id' => $agent->id,
                        'agent_username' => $agent->user_name,
                        'agent_type' => $agent->type,
                    ]);
                }
            }
            
            // If no agent found from banker, try to find agent by shan_agent_code from player
            if (!$agent && $firstPlayer->shan_agent_code) {
                $agent = User::where('shan_agent_code', $firstPlayer->shan_agent_code)
                            ->where('type', 20) // Ensure it's an agent
                            ->first();
                
                if ($agent) {
                    Log::info('ShanTransaction: Found agent by shan_agent_code', [
                        'player_id' => $firstPlayer->id,
                        'player_username' => $firstPlayer->user_name,
                        'shan_agent_code' => $firstPlayer->shan_agent_code,
                        'agent_id' => $agent->id,
                        'agent_username' => $agent->user_name,
                    ]);
                }
            }
            
            // If no agent found by shan_agent_code, try by agent_id
            if (!$agent && $firstPlayer->agent_id) {
                $agent = User::find($firstPlayer->client_agent_id);
                
                // Verify it's actually an agent
                if ($agent && $agent->type != 20) {
                    $agent = null; // Not a valid agent
                }
                
                if ($agent) {
                    Log::info('ShanTransaction: Found agent by agent_id', [
                        'player_id' => $firstPlayer->id,
                        'player_username' => $firstPlayer->user_name,
                        'agent_id' => $agent->id,
                        'agent_username' => $agent->user_name,
                    ]);
                }
            }

            // If still no agent found, try to find by common agent codes
            if (!$agent) {
                $commonAgentCodes = ['A3H4', 'A3H2', 'MK77', 'AG72', 'AG73']; // Common agent codes from production + new defaults
                foreach ($commonAgentCodes as $code) {
                    $agent = User::where('shan_agent_code', $code)
                                ->where('type', 20)
                                ->first();
                    if ($agent) {
                        Log::info('ShanTransaction: Found agent by common code', [
                            'agent_code' => $code,
                            'agent_id' => $agent->id,
                            'agent_username' => $agent->user_name,
                        ]);
                        Log::warning('ShanTransaction: Using default agent by common code', [
                            'player_id' => $firstPlayer->id,
                            'player_username' => $firstPlayer->user_name,
                            'agent_code' => $code,
                            'agent_id' => $agent->id,
                            'agent_username' => $agent->user_name,
                        ]);
                        break;
                    }
                }
            }

            // If still no agent found, try to find by the exact usernames
            if (!$agent) {
                $defaultAgentUsernames = ['AG72360789', 'AG72361782'];
                foreach ($defaultAgentUsernames as $username) {
                    $agent = User::where('user_name', $username)
                                ->where('type', 20)
                                ->first();
                    if ($agent) {
                        Log::info('ShanTransaction: Found agent by default username', [
                            'agent_username' => $username,
                            'agent_id' => $agent->id,
                            'agent_shan_code' => $agent->shan_agent_code,
                        ]);
                        Log::warning('ShanTransaction: Using default agent by username', [
                            'player_id' => $firstPlayer->id,
                            'player_username' => $firstPlayer->user_name,
                            'agent_username' => $username,
                            'agent_id' => $agent->id,
                        ]);
                        break;
                    }
                }
            }

            // If still no agent found, get the first available agent
            if (!$agent) {
                $agent = User::where('type', 20)->first();
                
                if ($agent) {
                    Log::warning('ShanTransaction: No agent found for player, using default agent', [
                        'player_id' => $firstPlayer->id,
                        'player_username' => $firstPlayer->user_name,
                        'default_agent_id' => $agent->id,
                        'default_agent_username' => $agent->user_name,
                    ]);
                }
            }

            $secretKey = $agent?->shan_secret_key;
            $callbackUrlBase = $agent?->shan_callback_url;

            Log::info('ShanTransaction: Agent information', [
                'agent_id' => $agent?->id,
                'agent_username' => $agent?->user_name,
                'agent_type' => $agent?->type,
                'agent_shan_code' => $agent?->shan_agent_code,
                'has_secret_key' => !empty($secretKey),
                'has_callback_url' => !empty($callbackUrlBase),
            ]);

            // Step 5: Generate unique wager_code if not provided
            if (!$wagerCode) {
                do {
                    $wagerCode = Str::random(12);
                } while (ReportTransaction::where('wager_code', $wagerCode)->exists());
            }

            Log::info('ShanTransaction: Using wager_code', ['wager_code' => $wagerCode]);

            // Step 6: Process each player's transaction
            $totalPlayerNet = 0;
            $processedPlayers = [];
            $callbackPlayers = [];
            $actualPlayersProcessed = 0;



            // Initialize results array
            $results = [];

            // Step 7: Use agent as banker instead of system wallet
            if (!$agent) {
                Log::error('ShanTransaction: No agent found for transaction');
                return $this->error('No agent found', 'No agent available for this transaction', 500);
            }

            // Use agent as the banker
            $banker = $agent;
            Log::info('ShanTransaction: Using agent as banker', [
                'banker_id' => $banker->id,
                'banker_username' => $banker->user_name,
                'agent_type' => $banker->type,
            ]);

            // Capture agent balance before player transactions
            $agentBeforeBalance = $banker->balanceFloat;

            try {
                DB::beginTransaction();

                foreach ($validated['players'] as $playerData) {
                $player = User::where('user_name', $playerData['player_id'])->first();
                
                if (!$player) {
                    Log::warning('ShanTransaction: Player not found during processing', [
                        'player_id' => $playerData['player_id'],
                    ]);
                    throw new \RuntimeException("Player not found: {$playerData['player_id']}");
                }
                
                // Skip if this is actually an agent (banker) - they'll be handled separately
                if (in_array($player->type, [10, 20])) {
                    Log::info('ShanTransaction: Skipping agent in player processing', [
                        'player_id' => $player->user_name,
                        'player_type' => $player->type,
                        'is_banker' => true,
                    ]);
                    continue;
                }
                
                $actualPlayersProcessed++;

                Log::info('ShanTransaction: Processing player', [
                    'player_id' => $player->id,
                    'username' => $player->user_name,
                    'agent_id' => $agent?->id,
                    'agent_name' => $agent?->user_name,
                    'player_data' => $playerData,
                ]);

                // Capture balance before transaction
                $beforeBalance = $player->balanceFloat;
                
                // Use amount_changed from request instead of calculating
                $amountChanged = $playerData['amount_changed'];
                $betAmount = $playerData['bet_amount'];
                $winLoseStatus = $playerData['win_lose_status'];
                
                // Calculate net amount for this player based on win/lose status
                // If player wins (status 1), amount_changed is positive for player (negative for banker)
                // If player loses (status 0), amount_changed is negative for player (positive for banker)
                $playerNetAmount = $winLoseStatus == 1 ? $amountChanged : -$amountChanged;
                $totalPlayerNet += $playerNetAmount;

                // Update wallet based on win/lose status
                if ($winLoseStatus == 1) {
                    // Player wins - Agent pays the player
                    $this->walletService->forceTransfer(
                        $banker, // Agent pays
                        $player,
                        $amountChanged,
                        TransactionName::Win,
                        [
                            'reason' => 'player_win',
                            'game_type_id' => $gameTypeId,
                            'wager_code' => $wagerCode,
                            'bet_amount' => $betAmount,
                        ]
                    );
                } else {
                    // Player loses - Player pays the agent
                    $this->walletService->forceTransfer(
                        $player,
                        $banker, // Player pays agent
                        $amountChanged,
                        TransactionName::Loss,
                        [
                            'reason' => 'player_lose',
                            'game_type_id' => $gameTypeId,
                            'wager_code' => $wagerCode,
                            'bet_amount' => $betAmount,
                        ]
                    );
                }

                // Refresh player balance
                $player->refresh();
                $afterBalance = $player->balanceFloat;

                // Store transaction history with all required fields
                ReportTransaction::create([
                    'user_id' => $player->id,
                    'agent_id' => $agent?->id, // Use found agent ID
                    'member_account' => $player->user_name,
                    'transaction_amount' => $amountChanged,
                    'status' => $winLoseStatus,
                    'bet_amount' => $betAmount,
                    'valid_amount' => $betAmount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'banker' => 0,
                    'wager_code' => $wagerCode,
                    'settled_status' => $winLoseStatus == 1 ? 'settled_win' : 'settled_loss',
                ]);

                // Add to results for API response
                $results[] = [
                    'player_id' => $player->user_name,
                    'balance' => $afterBalance,
                ];

                // Add to callback players (exclude agent)
                if ($player->user_name !== $banker->user_name) {
                    $callbackPlayers[] = [
                        'player_id' => $player->user_name,
                        'balance' => $afterBalance,
                    ];
                }

                $processedPlayers[] = array_merge($playerData, [
                    'current_balance' => $afterBalance,
                ]);

                Log::info('ShanTransaction: Player transaction completed', [
                    'player_id' => $player->id,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'amount_changed' => $amountChanged,
                ]);
            }
            
            // Calculate banker amount change correctly
            // totalPlayerNet is the net change for all players combined
            // Banker's change is the opposite of players' net change
            $bankerAmountChange = -$totalPlayerNet; // Banker gains what players lose (opposite sign)

            Log::info('ShanTransaction: Processing agent banker transaction', [
                'banker_id' => $banker->id,
                'banker_username' => $banker->user_name,
                'total_player_net' => $totalPlayerNet,
                'banker_amount_change' => $bankerAmountChange,
                'banker_gains' => $bankerAmountChange > 0 ? 'Yes' : 'No',
                'agent_before_player_transactions' => $agentBeforeBalance,
            ]);

            // Agent is the banker - no additional transfer needed
            // The agent already received/paid in the player transactions above
            Log::info('ShanTransaction: Agent is banker - no additional transfer needed', [
                'banker_id' => $banker->id,
                'total_player_net' => $totalPlayerNet,
                'banker_amount_change' => $bankerAmountChange,
            ]);

                // Refresh agent balance
                $banker->refresh();
                $agentAfterBalance = $banker->balanceFloat;

                // Store agent banker transaction
                ReportTransaction::create([
                    'user_id' => $banker->id,
                    'agent_id' => $agent?->id, // Use found agent ID
                    'member_account' => $banker->user_name,
                    'transaction_amount' => abs($bankerAmountChange),
                    'status' => $bankerAmountChange >= 0 ? 1 : 0,
                    'bet_amount' => null,
                    'valid_amount' => null,
                    'before_balance' => $agentBeforeBalance,
                    'after_balance' => $agentAfterBalance,
                    'banker' => 1,
                    'wager_code' => $wagerCode,
                    'settled_status' => $bankerAmountChange >= 0 ? 'settled_win' : 'settled_loss',
                ]);

                // Add agent banker to results
                $results[] = [
                    'player_id' => $banker->user_name,
                    'balance' => $agentAfterBalance,
                ];

                Log::info('ShanTransaction: Agent banker transaction completed', [
                    'banker_id' => $banker->id,
                    'before_balance' => $agentBeforeBalance,
                    'after_balance' => $agentAfterBalance,
                    'amount_changed' => $bankerAmountChange,
                    'actual_balance_change' => $agentAfterBalance - $agentBeforeBalance,
                ]);

                DB::commit();

                Log::info('ShanTransaction: All transactions committed successfully', [
                    'wager_code' => $wagerCode,
                    'total_player_net' => $totalPlayerNet,
                    'banker_amount_change' => $bankerAmountChange,
                    'processed_players_count' => count($processedPlayers),
                    'actual_players_processed' => $actualPlayersProcessed,
                    'agent_balance' => $banker->balanceFloat,
                ]);

                // Step 8: Send callback to client site
                if ($callbackUrlBase && $secretKey) {
                    $this->sendCallbackToClient(
                        $callbackUrlBase,
                        $wagerCode,
                        $gameTypeId,
                        $callbackPlayers,
                        $agentAfterBalance,
                        $totalPlayerNet,
                        $bankerAmountChange,
                        $secretKey
                    );
                } else {
                    Log::warning('ShanTransaction: Skipping callback - missing URL or secret key', [
                        'has_callback_url' => !empty($callbackUrlBase),
                        'has_secret_key' => !empty($secretKey),
                    ]);
                }

                Log::info('ShanTransaction: Transaction completed successfully', [
                    'wager_code' => $wagerCode,
                    'results' => $results,
                ]);

                return $this->success([
                    'status' => 'success',
                    'wager_code' => $wagerCode,
                    'players' => $processedPlayers,
                    'banker' => [
                        'player_id' => $banker->user_name,
                        'balance' => $agentAfterBalance,
                    ],
                    'agent' => [
                        'player_id' => $banker->user_name,
                        'balance' => $agentAfterBalance,
                    ],
                ], 'Transaction Successful');

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('ShanTransaction: Transaction failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'wager_code' => $wagerCode ?? 'not_generated',
                ]);
                return $this->error('Transaction failed', $e->getMessage(), 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('ShanTransaction: Validation failed', [
                'errors' => $e->errors(),
                'payload' => $request->all(),
            ]);
            return $this->error('Validation failed', $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('ShanTransaction: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('Unexpected error', $e->getMessage(), 500);
        }
    }

    /**
     * Send callback to client site
     */
    private function sendCallbackToClient(
        string $callbackUrlBase,
        string $wagerCode,
        int $gameTypeId,
        array $callbackPlayers,
        float $agentBalance,
        float $totalPlayerNet,
        float $bankerAmountChange,
        string $secretKey
    ): void {
        $callbackUrl = $callbackUrlBase . '/api/shan/client/balance-update';

        $callbackPayload = [
            'wager_code' => $wagerCode,
            'game_type_id' => $gameTypeId,
            'players' => $callbackPlayers,
            'banker_balance' => $agentBalance, // Keep banker_balance for client compatibility
            'agent_balance' => $agentBalance,  // Also include agent_balance for clarity
            'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeImmutable::ISO8601),
            'total_player_net' => $totalPlayerNet,
            'banker_amount_change' => $bankerAmountChange,
        ];

        // Generate signature for security
        ksort($callbackPayload);
        $signature = hash_hmac('md5', json_encode($callbackPayload), $secretKey);
        $callbackPayload['signature'] = $signature;

        try {
            $client = new Client();
            $response = $client->post($callbackUrl, [
                'json' => $callbackPayload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Transaction-Key' => 'yYpfrVcWmkwxWx7um0TErYHj4YcHOOWr',
                ],
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('ShanTransaction: Callback successful', [
                    'callback_url' => $callbackUrl,
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'wager_code' => $wagerCode,
                ]);
            } else {
                Log::error('ShanTransaction: Callback failed with non-2xx status', [
                    'callback_url' => $callbackUrl,
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'wager_code' => $wagerCode,
                ]);
            }

        } catch (RequestException $e) {
            Log::error('ShanTransaction: Callback failed (RequestException)', [
                'callback_url' => $callbackUrl,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response',
                'wager_code' => $wagerCode,
            ]);
        } catch (\Exception $e) {
            Log::error('ShanTransaction: Callback failed (General Exception)', [
                'callback_url' => $callbackUrl,
                'error' => $e->getMessage(),
                'wager_code' => $wagerCode,
            ]);
        }
    }
}


