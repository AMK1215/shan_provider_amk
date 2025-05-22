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
use App\Models\GameList; // Assuming you have a GameList model
// If your game list is structured across multiple models (e.g., Product, GameTypeProduct),
// you might need to import and use them here for validation.
// For this example, we assume GameList directly holds game_code, product_code, and game_type.
use Carbon\Carbon;

class LaunchGameController extends Controller
{
    
    /**
     * Handles the game launch request from the seamless wallet system.
     *
     * @param Request $request The incoming HTTP request containing game launch details.
     * @return \Illuminate\Http\JsonResponse
     */
    // Define constants as used in the provided code for the external API payload
    private const LANGUAGE_CODE = 0;
    private const PLATFORM_WEB = 'WEB';
    private const PLATFORM_DESKTOP = 'DESKTOP';
    private const PLATFORM_MOBILE = 'MOBILE';

    /**
     * Generate a secure token for game authentication
     * 
     * @param User $user
     * @return string
     */
    private function generateGameToken(User $user): string
    {
        $timestamp = Carbon::now()->timestamp;
        $randomString = Str::random(16);
        $userIdentifier = $user->id;
        
        // Create a unique token combining user ID, timestamp, and random string
        $tokenBase = $userIdentifier . '|' . $timestamp . '|' . $randomString;
        
        // Encrypt the token using the application key
        $encryptedToken = encrypt($tokenBase);
        
        // Store the token in cache with expiration (e.g., 1 hour)
        $cacheKey = 'game_token_' . $user->id;
        cache()->put($cacheKey, $encryptedToken, Carbon::now()->addHour());
        
        return $encryptedToken;
    }

    /**
     * Verify if a game token is valid
     * 
     * @param string $token
     * @param User $user
     * @return bool
     */
    private function verifyGameToken(string $token, User $user): bool
    {
        $cacheKey = 'game_token_' . $user->id;
        $storedToken = cache()->get($cacheKey);
        
        if (!$storedToken || $storedToken !== $token) {
            return false;
        }
        
        return true;
    }

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

        try {
            $validatedData = $request->validate([
               
                'game_code' => 'required|string',
                'product_code' => 'required|string',
                'game_type' => 'required|string'
                // 'sign' => 'required|string',
                // 'nickname' => 'nullable|string'
            ]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Launch Game API Validation Failed', ['errors' => $e->errors()]);
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        // Use config values for agent_code, secret_key, api_url, and api_currency
        $agentCode = config('seamless_key.agent_code');
        $secretKey = config('seamless_key.secret_key');
        $apiUrl = config('seamless_key.api_url') . '/api/operators/launch-game';
        $apiCurrency = config('seamless_key.api_currency');
        $operatorLobbyUrl = 'https://amk-movies-five.vercel.app';

        // Set request_time to now() in GMT+8 as integer timestamp (seconds)
        $nowGmt8 = now('Asia/Shanghai'); // GMT+8
        $requestTime = $nowGmt8->timestamp; // integer seconds

        // Generate signature for the request
        $signature = md5(
            $requestTime . $secretKey . 'launchgame' . $agentCode
        );

        // Verify incoming signature
        if (strtolower($validatedData['sign']) !== strtolower($signature)) {
            Log::warning('Invalid signature for launch game', [
                'provided' => $validatedData['sign'],
                'expected' => $signature
            ]);
            return ApiResponseService::error(
                SeamlessWalletCode::InvalidSignature,
                'Invalid signature'
            );
        }

        // Generate a secure token for this game session
        $gameToken = $this->generateGameToken($user);

        // Prepare the payload in PascalCase as per API spec
        $payload = [
            'operator_code' => $agentCode,
            'member_account' => $user->user_name,
            'password' => $gameToken, // Using secure token instead of actual password
            'nickname' => $validatedData['nickname'] ?? $user->name,
            'currency' => $apiCurrency,
            'game_code' => $validatedData['game_code'],
            'product_code' => $validatedData['product_code'],
            'game_type' => $validatedData['game_type'],
            'language_code' => self::LANGUAGE_CODE,
            'ip' => $request->ip(),
            'platform' => self::PLATFORM_WEB,
            'sign' => $signature,
            'request_time' => $requestTime,
            'operator_lobby_url' => $operatorLobbyUrl
        ];

        Log::info('Sending Launch Game Request to Provider', ['url' => $apiUrl, 'payload' => $payload]);

        try {
            $response = \Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($apiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Provider Launch Game API Response', ['response' => $responseData]);
                // Ensure response structure matches the documentation
                return response()->json([
                    'code'    => $responseData['code'] ?? 500,
                    'message' => $responseData['message'] ?? '',
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


    