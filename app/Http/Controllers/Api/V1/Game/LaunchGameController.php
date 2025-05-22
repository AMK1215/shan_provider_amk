<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Enums\SeamlessWalletCode;
use App\Models\GameList; // Assuming you have a GameList model
// If your game list is structured across multiple models (e.g., Product, GameTypeProduct),
// you might need to import and use them here for validation.
// For this example, we assume GameList directly holds game_code, product_code, and game_type.

class LaunchGameController extends Controller
{
    /**
     * Handles the game launch request from the seamless wallet system.
     *
     * @param Request $request The incoming HTTP request containing game launch details.
     * @return \Illuminate\Http\JsonResponse
     */
    public function launchGame(Request $request)
    {
        // Log the incoming request for debugging and auditing purposes
        Log::info('Launch Game API Request', ['request' => $request->all()]);

        try {
            // Validate the incoming request data
            $request->validate([
                'member_account' => 'required|string',
                'game_code' => 'required|string',
                'product_code' => 'required|string',
                'game_type' => 'required|string',
                'operator_code' => 'required|string',
                'currency' => 'required|string',
                'sign' => 'required|string',
                'request_time' => 'required|integer', // Unix timestamp in seconds or milliseconds
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors and return a structured error response
            Log::warning('Launch Game API Validation Failed', ['errors' => $e->errors()]);
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError, // Or a more specific validation error code if available
                'Validation failed',
                $e->errors()
            );
        }

        // Retrieve the secret key and API URL from configuration
        $secretKey = Config::get('seamless_key.secret_key');
        $operatorApiUrl = Config::get('seamless_key.api_url'); // Get the operator API URL from config

        // Calculate the expected signature for verification
        // The action string 'launchgame' is crucial and must match the provider's specification
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'launchgame' . // This string is part of the signature calculation
            $secretKey
        );

        // Compare the provided signature with the expected signature
        if (strtolower($request->sign) !== strtolower($expectedSign)) {
            Log::warning('Launch Game Invalid Signature', ['provided' => $request->sign, 'expected' => $expectedSign]);
            return ApiResponseService::error(
                SeamlessWalletCode::InvalidSignature,
                'Invalid signature'
            );
        }

        // Extract relevant data from the request
        $memberAccount = $request->member_account;
        $gameCode = $request->game_code;
        $productCode = $request->product_code;
        $gameType = $request->game_type;
        $currency = $request->currency; // Currency might be needed for game launch URL

        // 1. Check if the member (user) exists in your system
        $user = User::where('user_name', $memberAccount)->first();
        if (!$user) {
            Log::warning('Member not found for game launch', ['member_account' => $memberAccount]);
            return ApiResponseService::error(
                SeamlessWalletCode::MemberNotExist,
                'Member not found'
            );
        }

        // 2. Validate the game_code, product_code, and game_type against your GameList data.
        // This assumes your `GameList` model has columns named 'game_code', 'product_code', and 'game_type'.
        // Adjust the query if your schema is different (e.g., joining with Product or GameTypeProduct tables).
        $game = GameList::where('game_code', $gameCode)
                        ->where('product_code', $productCode)
                        ->where('game_type', $gameType)
                        ->first();

        if (!$game) {
            Log::warning('Game not found or invalid product/game type combination', [
                'game_code' => $gameCode,
                'product_code' => $productCode,
                'game_type' => $gameType,
                'member_account' => $memberAccount
            ]);
            return ApiResponseService::error(
                SeamlessWalletCode::GameListNotFound, // Assuming this enum case exists for game not found
                'Game not found or invalid product/game type combination'
            );
        }

        // Generate a simple unique session token for the player.
        // In a real-world scenario, this token would be more complex and secure.
        // It might be a JWT (JSON Web Token), a cryptographically signed string,
        // or a token obtained from an authentication service.
        // For this example, we're using uniqid() for a simple unique string.
        $sessionToken = uniqid($user->id . '_', true);

        // 3. Game Launch Logic (Integrate with actual game provider API)
        // This is the core part where you would generate the actual game launch URL.
        // This often involves:
        //    - Making an API call to the game provider with user details, game ID, currency, etc.
        //    - Receiving a unique game session URL or token from the provider.
        //    - Potentially creating a local game session record.
        //
        // Using the operatorApiUrl from config
        $gameUrl = rtrim($operatorApiUrl, '/') . '/operators/launch-game?' .
                   'game_id=' . $game->game_code .
                   '&player_id=' . $user->id .
                   '&currency=' . $currency .
                   '&token=' . $sessionToken; // Use the generated session token here

        Log::info('Game launched successfully', [
            'member_account' => $memberAccount,
            'game_code' => $gameCode,
            'game_url' => $gameUrl // Log the generated URL
        ]);

        // Return a success response with the game launch URL
        return ApiResponseService::success([
            'member_account' => $memberAccount,
            'game_code' => $gameCode,
            'game_url' => $gameUrl, // This is the URL the client will redirect to
            'message' => 'Game launched successfully'
        ]);
    }

    // Note: The buildErrorResponse helper is typically part of ApiResponseService,
    // so it's not explicitly defined here to avoid redundancy.



}


    