<?php

namespace App\Http\Controllers\Api\V1\DigitGame;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Enums\TransactionName;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\HttpResponses;
use App\Models\DigitGame\DigitBet;
use Illuminate\Support\Str;
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

        // Check total bet vs player balance BEFORE any bet placed
        $totalBetAmount = array_sum(array_column($bets, 'bet_amount'));
        if ($user->balanceFloat < $totalBetAmount) {
            return $this->error(null, "Insufficient balance for total bet: {$totalBetAmount} MMK", 400);
        }

        $results = [];

        DB::beginTransaction();
        try {
            foreach ($bets as $data) {
                // Withdraw bet amount
                $walletService->withdraw($user, $data['bet_amount'], TransactionName::DigitBet, [
                    'game' => 'digit_slot',
                    'desc' => 'Digit Bet',
                ]);

                // Payout if win
                if ($data['win_amount'] > 0) {
                    $walletService->deposit($user, $data['win_amount'], TransactionName::GameWin, [
                        'game' => 'digit_slot',
                        'desc' => 'Win Payout',
                    ]);
                }

                // Always set user_id and member_account
                $data['user_id'] = $user->id;
                $data['member_account'] = $user->user_name ?? '';
                $data['wager_code'] = Str::random(10);

                $results[] = DigitBet::create($data);
            }

            // Commit bets and wallet changes
            DB::commit();

            // Refresh user to get latest balance
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
