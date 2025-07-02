<?php

namespace App\Http\Controllers\Api\V1\DigitGame;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Enums\TransactionName;
use Illuminate\Support\Facades\Auth;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\DB;
use App\Models\DigitGame\DigitBet;
class DigitSlotController extends Controller
{
    use HttpResponses;

    public function bet(Request $request, WalletService $walletService)
    {
        $user = Auth::user();
    
        // Validate input: array of bets
        $bets = $request->validate([
            'bets' => 'required|array|min:1',
            'bets.*.bet_type' => 'required|string',
            'bets.*.digit' => 'nullable|integer|min:0|max:9',
            'bets.*.bet_amount' => 'required|numeric|min:1',
            'bets.*.multiplier' => 'required|numeric',
            'bets.*.rolled_number' => 'required|integer|min:0|max:9',
            'bets.*.win_amount' => 'required|numeric',
            'bets.*.profit' => 'required|numeric',
            'bets.*.status' => 'required|string',
            'bets.*.bet_time' => 'required|date',
            'bets.*.outcome' => 'required|string',
            'bets.*.before_balance' => 'required|numeric',
            'bets.*.after_balance' => 'required|numeric',
        ])['bets'];
    
        $results = [];
    
        DB::beginTransaction();
        try {
            foreach ($bets as $data) {
                // Always refresh user after each bet (to get latest balance)
                $user->refresh();
    
                // Check player balance
                if ($user->balanceFloat < $data['bet_amount']) {
                    // Rollback immediately if not enough balance
                    DB::rollBack();
                    return $this->error(null, 'Insufficient balance for bet: ' . $data['bet_amount'], 400);
                }
    
                // Withdraw bet amount
                $walletService->withdraw($user, $data['bet_amount'], TransactionName::DigitBet, [
                    'game' => 'digit_slot',
                    'desc' => 'Digit Bet',
                ]);
    
                // Deposit win if any
                if ($data['win_amount'] > 0) {
                    $walletService->deposit($user, $data['win_amount'], TransactionName::GameWin, [
                        'game' => 'digit_slot',
                        'desc' => 'Win Payout',
                    ]);
                }
    
                // Save bet record (again, refresh for latest wallet state)
                $user->refresh();
                $data['before_balance'] = $user->balanceFloat + $data['bet_amount'] - $data['win_amount'];
                $data['after_balance'] = $user->balanceFloat;
                $results[] = DigitBet::create($data);
            }
            DB::commit();
    
            // Return latest user balance as well, for frontend refresh
            $user->refresh();
            return $this->success([
                'bets' => $results,
                'balance' => $user->balanceFloat,
            ], 'All bets placed successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error(null, "Bet failed: " . $e->getMessage(), 500);
        }
    }
    
}
