<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Enums\SeamlessWalletCode;
use App\Http\Controllers\Controller;
use App\Models\GameList;
use App\Models\User;
use App\Services\ApiResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Make sure to use Http facade for making requests

class LaunchGameController extends Controller
{
    


    /**
     * Provider Launch Game - receives request and responds with launch game URL
     * This is a provider endpoint that builds and returns game URLs to client sites
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function launchGameForClient(Request $request)
    {
        Log::info('Provider Launch Game Request', ['request' => $request->all()]);

        try {
            $validatedData = $request->validate([
                'agent_code' => 'required|string',
                'product_code' => 'required|integer',
                'game_type' => 'required|string',
                'member_account' => 'required|string',
                'nickname' => 'nullable|string',
            ]);

            // Use MMK currency for all products
            $apiCurrency = 'MMK';
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Provider Launch Game Validation Failed', ['errors' => $e->errors()]);

            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Get or create user from member_account
        $memberAccount = $request->member_account;
        $clientUser = User::where('user_name', $memberAccount)->first();
        
        // If no client user in our db users table, create automatically
        if (!$clientUser) {
            $clientUser = User::create([
                'user_name' => $memberAccount,
                'name' => $memberAccount,
                'password' => Hash::make($memberAccount),
                'type' => UserType::Player->value,
                'status' => 1,
                'is_changed_password' => 1,
            ]);
            Log::info('Created new user for provider launch game', ['member_account' => $memberAccount]);
        }

        // Get user balance
       // $balance = $clientUser->balanceFloat ?? 0;
       $balance = $request->balance;

        // Build launch game URL with Shan provider configuration
        $launchGameUrl = sprintf(
            'https://ponewine20x.xyz/?user_name=%s&balance=%s&product_code=%s&game_type=%s&agent_code=%s',
            urlencode($memberAccount),
            $balance,
            $validatedData['product_code'],
            $validatedData['game_type'],
            $validatedData['agent_code']
        );

        Log::info('Provider Launch Game URL generated', [
            'member_account' => $memberAccount,
            'balance' => $balance,
            'product_code' => $validatedData['product_code'],
            'game_type' => $validatedData['game_type'],
            'launch_game_url' => $launchGameUrl
        ]);

        // Return the launch game URL to client site
        return response()->json([
            'code' => 200,
            'message' => 'Game launched successfully',
            'url' => $launchGameUrl,
        ]);
    }

    
}
