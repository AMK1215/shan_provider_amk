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
use GuzzleHttp\Exception\RequestException; // Import RequestException for specific error handling

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
        ]);

        // Step 1: Validate
        $validated = $request->validate([
            'banker' => 'required|array',
            'banker.player_id' => 'required|string',
            'players' => 'required|array',
            'players.*.player_id' => 'required|string',
            'players.*.bet_amount' => 'required|numeric|min:0',
            'players.*.win_lose_status' => 'required|integer|in:0,1'
            // Add game_type_id to validation if it's coming from the request
            // 'game_type_id' => 'required|integer',
        ]);

        // --- Start of Agent Secret Key Retrieval ---
        // Corrected variable name from $validatedData to $validated
        $player_id = $validated['players'][0]['player_id']; // Get the first player's ID for agent lookup
        $player = User::where('user_name', $player_id)->first();

        if (!$player) {
            return $this->error('Player not found', 'Player not found', 404);
        }

        $player_agent_code = $player->shan_agent_code; // Get the agent code associated with the player

        // Find the agent using the shan_agent_code
        // Optional: Ensure it's an actual agent role using ->where('type', User::AGENT_ROLE)
        $agent = User::where('shan_agent_code', $player_agent_code)->first();

        if (!$agent) {
            return $this->error('Agent not found for player\'s agent code', 'Agent not found', 404);
        }

        // Now you can access the secret key directly from the $agent object
        $secret_key = $agent->shan_secret_key;
        $callback_url_base = $agent->shan_callback_url; // Get the base callback URL

        if (!$secret_key) {
            // This means the agent was found, but their secret_key field is null or empty
            return $this->error('Secret Key not set for agent', 'Secret Key not set', 404);
        }
        if (!$callback_url_base) { // Check if callback URL is set
            return $this->error('Callback URL not set for agent', 'Callback URL not set', 404);
        }
        // --- End of Agent Secret Key Retrieval ---

        // CRITICAL SECURITY FIX: Mask secret key for logging in production
        Log::info('Agent Secret Key Retrieved', [
            'agent_username' => $agent->user_name,
            'secret_key' => Str::mask($secret_key, '*', 0, max(0, strlen($secret_key) - 4)) // Mask it!
        ]);

        // agent credit
        $agent_balance = $agent->wallet->balanceFloat;
        if ($agent_balance < 0) {
            return $this->error('Agent balance is negative', 'Agent balance is negative', 404);
        }

        Log::info('Agent balance', [
            'agent_balance' => $agent_balance,
        ]);


        // Generate unique wager_code for idempotency
        do {
            $wager_code = Str::random(12);
        } while (ReportTransaction::where('wager_code', $wager_code)->exists());

        // Redundant check, can be removed. The do-while loop ensures uniqueness.
        // if (ReportTransaction::where('wager_code', $wager_code)->exists()) {
        //     return $this->error('Duplicate transaction!', 'This round already settled.', 409);
        // }

        $results = [];
        $totalPlayerNet = 0; // player net (win - lose) for this round

        try {
            DB::beginTransaction();

            // PLAYERS: Process each player, calculate total net win/loss
            foreach ($validated['players'] as $playerData) {
                // OPTIMIZATION: Fetch all players at once before the loop to avoid N+1 queries
                // $playersCollection = User::whereIn('user_name', array_column($validated['players'], 'player_id'))->get()->keyBy('user_name');
                // $player = $playersCollection->get($playerData['player_id']);

                $player = User::where('user_name', $playerData['player_id'])->first();
                if (!$player) {
                    // CRITICAL: Don't just continue. This can lead to inconsistent state.
                    // Throw an exception to rollback the entire transaction if a player is not found.
                    throw new \RuntimeException("Player not found: {$playerData['player_id']}. Transaction aborted.");
                }

                $oldBalance = $player->wallet->balanceFloat;
                $betAmount = $playerData['bet_amount'];
                $winLose = $playerData['win_lose_status']; // 1 = win, 0 = lose

                // Win = bet amount ထပ်တိုး, Lose = bet amount နုတ်
                $amountChanged = ($winLose == 1) ? $betAmount : -$betAmount;
                $totalPlayerNet += $amountChanged;

                // Wallet update
                if ($amountChanged > 0) {
                    $this->walletService->deposit($player, $amountChanged, TransactionName::GameWin, [
                        'description' => 'Win from Shan game',
                        'wager_code' => $wager_code,
                        'bet_amount' => $betAmount,
                    ]);
                } elseif ($amountChanged < 0) {
                    $this->walletService->withdraw($player, abs($amountChanged), TransactionName::GameLoss, [
                        'description' => 'Loss in Shan game',
                        'wager_code' => $wager_code,
                        'bet_amount' => $betAmount,
                    ]);
                }

                $player->refresh();

                // Record transaction
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

            // BANKER: Use the system wallet (admin user) as banker instead of individual banker users
            $banker = User::adminUser();
            if (!$banker) {
                Log::error('ShanTransaction: System wallet (admin user) not found');
                return $this->error('System wallet not found', 'Banker (system wallet) not configured', 500);
            }

            Log::info('ShanTransaction: Using system wallet as banker', [
                'banker_id' => $banker->user_name,
                'balance' => $banker->wallet->balanceFloat,
            ]);
            $bankerOldBalance = $banker->wallet->balanceFloat;
            $bankerAmountChange = -$totalPlayerNet; // Banker always opposite of player total net

            if ($bankerAmountChange > 0) {
                $this->walletService->deposit($banker, $bankerAmountChange, TransactionName::BankerDeposit, [
                    'description' => 'Banker receive (from all players)',
                    'wager_code' => $wager_code
                ]);
            } elseif ($bankerAmountChange < 0) {
                $this->walletService->withdraw($banker, abs($bankerAmountChange), TransactionName::BankerWithdraw, [
                    'description' => 'Banker payout (to all players)',
                    'wager_code' => $wager_code
                ]);
            }
            // If $bankerAmountChange == 0, do nothing

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

            // --- CRITICAL: DB::commit() MUST happen BEFORE sending callback ---
            // If the callback fails, you don't want to roll back your local transaction.
            // Your local transaction should be finalized first.
            DB::commit();

            // client site player's balance update with call back url
            $callback_url = $callback_url_base . '/client/balance-update'; // Construct full URL

            // Prepare the callback payload
            $callbackPayload = [
                'wager_code' => $wager_code,
                // Ensure game_type_id is validated and available from $validated
                'game_type_id' => $validated['game_type_id'] ?? null,
                // Filter out the banker from the 'players' array for the client site
                'players' => collect($results)->filter(fn($r) => $r['player_id'] !== $banker->user_name)->values()->all(),
                'banker_balance' => $banker->wallet->balanceFloat, // Banker's final balance
                //'timestamp' => now()->toIso8601String(),
                'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeImmutable::ISO8601), // Current timestamp in ISO 8601 format
                'total_player_net' => $totalPlayerNet,
                'banker_amount_change' => $bankerAmountChange,
            ];

            // --- CRITICAL SECURITY ADDITION: Generate signature ---
            // The client site will use its secret key to verify this signature.
            $signature = hash_hmac('sha256', json_encode($callbackPayload), $secret_key);
            $callbackPayload['signature'] = $signature;

            try {
                $client = new Client();
                $response = $client->post($callback_url, [
                    'json' => $callbackPayload,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        // Optional: Add an Authorization header if the client site requires it
                        // 'Authorization' => 'Bearer your_provider_token',
                    ],
                    'timeout' => 5, // Timeout for the request in seconds
                    'connect_timeout' => 5, // Connection timeout
                ]);

                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody()->getContents();

                if ($statusCode >= 200 && $statusCode < 300) {
                    Log::info('ShanTransaction: Callback to client site successful', [
                        'callback_url' => $callback_url,
                        'status_code' => $statusCode,
                        'response_body' => $responseBody,
                        'wager_code' => $wager_code, // Add wager_code to log for easier tracking
                    ]);
                } else {
                    Log::error('ShanTransaction: Callback to client site failed with non-2xx status', [
                        'callback_url' => $callback_url,
                        'status_code' => $statusCode,
                        'response_body' => $responseBody,
                        'payload' => $callbackPayload,
                        'wager_code' => $wager_code,
                    ]);
                    // IMPORTANT: Implement a retry mechanism here for failed callbacks
                    // e.g., dispatch a job to a queue: dispatch(new SendCallbackJob($callback_url, $callbackPayload));
                }

            } catch (RequestException $e) { // Use specific Guzzle exception
                Log::error('ShanTransaction: Callback to client site failed (Guzzle RequestException)', [
                    'callback_url' => $callback_url,
                    'error' => $e->getMessage(),
                    'payload' => $callbackPayload,
                    'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response',
                    'wager_code' => $wager_code,
                ]);
                // IMPORTANT: Implement a retry mechanism
            } catch (\Exception $e) { // Catch any other general exceptions
                Log::error('ShanTransaction: Callback to client site failed (General Exception)', [
                    'callback_url' => $callback_url,
                    'error' => $e->getMessage(),
                    'payload' => $callbackPayload,
                    'wager_code' => $wager_code,
                ]);
                // IMPORTANT: Implement a retry mechanism
            }

            // Log success for the main transaction only after callback attempt
            Log::info('ShanTransaction: Transaction completed successfully', [
                'wager_code' => $wager_code,
                'total_player_net' => $totalPlayerNet,
                'banker_amount_change' => $bankerAmountChange,
                'system_wallet_balance' => $banker->wallet->balanceFloat,
                'results' => $results,
            ]);

            // This is the final response to the game client
            return $this->success($results, 'Transaction Successful');

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback if any error occurs before DB::commit()
            Log::error('ShanTransaction: Transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('Transaction failed', $e->getMessage(), 500);
        }
    }
}