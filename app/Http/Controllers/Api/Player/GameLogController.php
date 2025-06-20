<?php

namespace App\Http\Controllers\Api\Player;

use App\Http\Controllers\Controller;
use App\Models\PlaceBet;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GameLogController extends Controller
{
    use HttpResponses;

    public function index(Request $request)
    {
        $player = Auth::user();

        $gameLogs = PlaceBet::where('player_id', $player->id)
            ->where('wager_status', 'SETTLED')
            ->select(
                'game_name',
                DB::raw('COUNT(*) as spin_count'),
                DB::raw('SUM(bet_amount) as turnover'),
                DB::raw('SUM(prize_amount) as total_payout'),
                DB::raw('SUM(prize_amount) - SUM(bet_amount) as win_loss')
            )
            ->groupBy('game_name')
            ->orderBy('game_name')
            ->get();

        return $this->success($gameLogs, 'Player game logs retrieved successfully.');
    }
} 