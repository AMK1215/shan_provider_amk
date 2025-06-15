<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use App\Services\ShanTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShanTransactionController extends Controller
{
    public function __construct(
        private ShanTransactionService $transactionService
    ) {}

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'game_type_id' => 'required|integer',
                'players' => 'required|array|min:1',
                'players.*.player_id' => 'required|string',
                'players.*.bet_amount' => 'required|numeric',
                'players.*.amount_changed' => 'required|numeric',
                'players.*.win_lose_status' => 'required|integer|in:0,1',
            ]);

            $result = $this->transactionService->processTransaction($validated, $validated['players']);

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            Log::error('Unexpected error in ShanTransactionController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'fail',
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
