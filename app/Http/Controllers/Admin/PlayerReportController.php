<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlaceBet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;



class PlayerReportController extends Controller
{
    public function summary(Request $request)
    {
        $auth = Auth::user();

        // Base query with joins for efficiency
        $query = PlaceBet::query()
            ->join('users as player', 'place_bets.player_id', '=', 'player.id')
            ->join('users as agent', 'player.agent_id', '=', 'agent.id')
            ->selectRaw('
                player.user_name as player_user_name,
                agent.user_name as agent_user_name,
                COUNT(place_bets.id) as total_spins,
                SUM(COALESCE(place_bets.bet_amount, 0)) as total_bet,
                SUM(COALESCE(place_bets.prize_amount, 0)) as total_payout,
                SUM(COALESCE(place_bets.prize_amount, 0) - COALESCE(place_bets.bet_amount, 0)) as win_lose
            ');

        // Role-based filtering: Agents/SubAgents see their direct players only.
        if ($auth->hasRole('Agent') || $auth->hasRole('SubAgent')) {
            $playerIds = User::where('agent_id', $auth->id)->where('type', 'Player')->pluck('id');
            $query->whereIn('place_bets.player_id', $playerIds);
        }
        // Owners see all.

        // Date filtering
        $start_date = $request->input('start_date', Carbon::today()->toDateString());
        $end_date = $request->input('end_date', Carbon::today()->toDateString());
        
        $query->whereBetween('place_bets.created_at', [
            Carbon::parse($start_date)->startOfDay(),
            Carbon::parse($end_date)->endOfDay(),
        ]);

        // Player username filter
        if ($request->filled('member_account')) {
            $query->where('player.user_name', $request->member_account);
        }

        // Group results and execute query
        $report = $query->groupBy('player.user_name', 'agent.user_name')->get();

        // Calculate totals
        $totals = [
            'total_bet'    => $report->sum('total_bet'),
            'total_payout' => $report->sum('total_payout'),
            'win_lose'     => $report->sum('win_lose'),
        ];

        return view('admin.report.player_report_index', compact('report', 'totals'));
    }
}







