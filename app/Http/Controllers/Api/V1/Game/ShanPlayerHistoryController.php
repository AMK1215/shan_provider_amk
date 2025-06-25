<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\ReportTransaction; // Assuming this is the correct namespace for your model
use Illuminate\Support\Facades\Auth; // Import the Auth facade
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\GameType;

class ShanPlayerHistoryController extends Controller
{
    /**
     * Fetch transaction history for the authenticated player.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlayerHistory(Request $request)
    {
        // Check if a user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized. Please log in.',
                'status' => 'error'
            ], 401);
        }

        // Get the authenticated user's ID
        $userId = Auth::user()->id;

        // Define pagination parameters
        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $page = $request->input('page', 1);         // Default to page 1

        try {
            // Fetch transaction history for the authenticated user,
            // eager load the 'gameType' relationship for better performance
            // and order by latest transactions first.
            $history = ReportTransaction::where('user_id', $userId)
                ->with('gameType:id,name') // Select only id and name from gameType
                ->latest() // Order by latest transactions
                ->paginate($perPage, ['*'], 'page', $page); // Paginate the results

            // Return the paginated data as a JSON response
            return response()->json([
                'message' => 'Transaction history fetched successfully.',
                'status' => 'success',
                'data' => $history->items(), // Get the actual items for the current page
                'meta' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'from' => $history->firstItem(),
                    'to' => $history->lastItem(),
                ],
                'links' => [
                    'first' => $history->url(1),
                    'last' => $history->url($history->lastPage()),
                    'prev' => $history->previousPageUrl(),
                    'next' => $history->nextPageUrl(),
                ]
            ], 200);

        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error("Error fetching player history for user ID {$userId}: " . $e->getMessage());

            // Return an error response
            return response()->json([
                'message' => 'Failed to fetch transaction history. Please try again later.',
                'status' => 'error',
                'error' => $e->getMessage() // Only include error message in development/testing
            ], 500);
        }
    }
}
