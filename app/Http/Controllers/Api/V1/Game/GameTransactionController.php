<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use App\Services\ShanApiService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GameTransactionController extends Controller
{
    use HttpResponses;

    public function __construct(
        private ShanApiService $shanApiService
    ) {}

    public function processGameTransaction(Request $request)
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

            Log::info('Processing game transaction', [
                'validated_data' => $validated
            ]);

            // Format the data for Shan API
            $transactionData = $this->shanApiService->formatTransactionData(
                $validated['game_type_id'],
                $validated['players']
            );

            // Call Shan API
            $result = $this->shanApiService->processTransaction($transactionData);

            return $this->success($result, 'Transaction processed successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors()
            ]);
            return $this->error('', 'Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Transaction processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('', 'Transaction processing failed: ' . $e->getMessage(), 500);
        }
    }
} 