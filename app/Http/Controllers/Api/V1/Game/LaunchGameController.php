<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Enums\SeamlessWalletCode;
use App\Models\GameList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http; // Make sure to use Http facade for making requests

class LaunchGameController extends Controller
{
    private const LANGUAGE_CODE = 0; // Keeping as 0 as per your provided code
    private const PLATFORM_WEB = 'WEB';
    private const PLATFORM_DESKTOP = 'DESKTOP';
    private const PLATFORM_MOBILE = 'MOBILE';

    // Removed generateGameToken and verifyGameToken as they are no longer needed
    // for the 'password' field based on provider's clarification.
    // However, if your application uses them for other internal purposes, keep them.

    /**
     * Handles the game launch request.
     * This method validates the incoming request, authenticates the user,
     * generates a signature, constructs a payload, and makes an HTTP call
     * to an external game provider's launch API.
     *
     * @param Request $request The incoming HTTP request containing game launch details.
     * @return \Illuminate\Http\JsonResponse
     */
    public function launchGame(Request $request)
    {
        Log::info('Launch Game API Request', ['request' => $request->all()]);

        $user = Auth::user();
        if (!$user) {
            Log::warning('Unauthenticated user attempting game launch.');
            return ApiResponseService::error(
                SeamlessWalletCode::MemberNotExist,
                'Authentication required. Please log in.'
            );
        }

        try {
            $validatedData = $request->validate([
                //'game_code' => 'required|string',
                'product_code' => 'required|integer',
                'game_type' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Launch Game API Validation Failed', ['errors' => $e->errors()]);
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        // --- NEW LOGIC FOR GAME PROVIDER PASSWORD ---
        $gameProviderPassword = $user->getGameProviderPassword();

        // If the user doesn't have a game provider password yet, generate and store one
        if (!$gameProviderPassword) {
            // Generate a strong, unique, and consistent password for this player for the game provider
            // The provider states "The same password will always need to be used for the exact player after creation."
            $gameProviderPassword = Str::random(50); // Generates a 32-character random string
            $user->setGameProviderPassword($gameProviderPassword); // Saves and encrypts in DB
            Log::info('Generated and stored new game provider password for user', ['user_id' => $user->id]);
        }
        // --- END NEW LOGIC ---

        $agentCode = config('seamless_key.agent_code');
        $secretKey = config('seamless_key.secret_key');
        $apiUrl = config('seamless_key.api_url') . '/api/operators/launch-game';
        $apiCurrency = config('seamless_key.api_currency');
        $operatorLobbyUrl = 'https://amk-movies-five.vercel.app';

        $nowGmt8 = now('Asia/Shanghai');
        $requestTime = $nowGmt8->timestamp;

        $generatedSignature = md5(
            $requestTime . $secretKey . 'launchgame' . $agentCode
        );

        $payload = [
            'operator_code' => $agentCode,
            'member_account' => $user->user_name,
            'password' => $gameProviderPassword, // <-- Use the consistent password stored in your DB
            'nickname' => $request->input('nickname') ?? $user->name,
            'currency' => $apiCurrency,
            'game_code' => 'null',
            'product_code' => $validatedData['product_code'],
            'game_type' => $validatedData['game_type'],
            'language_code' => self::LANGUAGE_CODE,
            'ip' => $request->ip(),
            'platform' => self::PLATFORM_WEB,
            'sign' => $generatedSignature,
            'request_time' => $requestTime,
            'operator_lobby_url' => $operatorLobbyUrl,
        ];

        Log::info('Sending Launch Game Request to Provider', ['url' => $apiUrl, 'payload' => $payload]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($apiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Provider Launch Game API Response', ['response' => $responseData]);

                return response()->json([
                    'code'    => $responseData['code'] ?? SeamlessWalletCode::InternalServerError->value,
                    'message' => $responseData['message'] ?? 'Game launched successfully',
                    'url'     => $responseData['url'] ?? '',
                    'content' => $responseData['content'] ?? '',
                ]);
            }

            Log::error('Provider Launch Game API Request Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'request_payload' => $payload
            ]);
            return response()->json(
                ['code' => $response->status(), 'message' => 'Provider API request failed', 'url' => '', 'content' => $response->body()],
                $response->status()
            );
        } catch (\Throwable $e) {
            Log::error('Unexpected error during provider API call', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_payload' => $payload
            ]);
            return response()->json(
                ['code' => 500, 'message' => 'Unexpected error', 'url' => '', 'content' => $e->getMessage()],
                500
            );
        }
    }
}