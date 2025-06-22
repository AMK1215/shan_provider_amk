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
        $playerIds = collect(); // Initialize an empty collection for player IDs

        // Determine which players to display based on the authenticated user's role
        if ($auth->type === UserType::Agent || $auth->type === UserType::Owner) {
            // If the user is an Agent or Owner, they can see all their descendants' players
            $players = $auth->getAllDescendantPlayers();
            $playerIds = $players->pluck('id')->toArray();
        } elseif ($auth->type === UserType::SubAgent) {
            // If the user is a SubAgent, they should see players under their direct parent agent.
            // These players' agent_id will be the parent agent's ID.
            $parentAgent = $auth->agent; // Get the subagent's direct parent agent

            if ($parentAgent) {
                // Get all players that have this parentAgent's ID as their agent_id
                // This includes players created directly by the parent agent AND players
                // created by any subagent of this parent agent (like the current $auth subagent).
                $playersUnderParentAgent = User::where('agent_id', $parentAgent->id)
                                             ->where('type', UserType::Player)
                                             ->get();
                $playerIds = $playersUnderParentAgent->pluck('id')->toArray();
            } else {
                // Handle case where a subagent somehow doesn't have a parent agent (shouldn't happen with your current setup)
                $playerIds = []; // No players to show
            }
        } else {
            // For any other user type (e.g., Player), they might not have access to reports
            // Or you might handle it differently (e.g., show their own stats only)
            $playerIds = []; // No players to show by default for other types
        }

        $start = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $end = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        $placeBets = PlaceBet::query()
            ->whereIn('player_id', $playerIds)
            ->where('action', 'SETTLED'); // <<==== Only SETTLED bets

        if ($start) {
            $placeBets->where('created_at', '>=', $start.' 00:00:00');
        }
        if ($end) {
            $placeBets->where('created_at', '<=', $end.' 23:59:59');
        }

        if ($request->filled('member_account')) {
            $placeBets->whereHas('player', function($query) use ($request) {
                $query->where('user_name', $request->member_account);
            });
        }

        // Group by player
        $report = $placeBets
            ->selectRaw('
                player_id,
                COUNT(id) as total_spins,
                SUM(COALESCE(bet_amount, 0)) as total_bet,
                SUM(COALESCE(prize_amount, 0)) as total_payout,
                SUM(COALESCE(prize_amount, 0) - COALESCE(bet_amount, 0)) as win_lose
            ')
            ->groupBy('player_id')
            ->get();

        // Attach player and agent info
        $report = $report->map(function ($row) {
            $player = User::find($row->player_id);
            $row->player_user_name = $player?->user_name;
            // The agent of the player is the one whose ID is in player->agent_id
            $row->agent_user_name = $player?->agent?->user_name;
            return $row;
        });

        // Totals
        $totals = [
            'total_bet'    => $report->sum('total_bet'),
            'total_payout' => $report->sum('total_payout'),
            'win_lose'     => $report->sum('win_lose'),
        ];

        return view('admin.report.player_report_index', [
            'report' => $report,
            'totals' => $totals,
        ]);
    }
//     public function summary(Request $request)
// {
//     $auth = Auth::user();
//     $subAgent = $auth->agent;
//     $players = $auth->getAllDescendantPlayers();
//     $playerIds = $players->pluck('id')->toArray();

//     $start = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
//     $end = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

//     $placeBets = PlaceBet::query()
//         ->whereIn('player_id', $playerIds)
//         ->where('action', 'SETTLED'); // <<==== Only SETTLED bets

//     if ($start) {
//         $placeBets->where('created_at', '>=', $start.' 00:00:00');
//     }
//     if ($end) {
//         $placeBets->where('created_at', '<=', $end.' 23:59:59');
//     }

//     if ($request->filled('member_account')) {
//         $placeBets->where('member_account', $request->member_account);
//     }

//     // Group by player
//     $report = $placeBets
//         ->selectRaw('
//             player_id,
//             COUNT(id) as total_spins,
//             SUM(COALESCE(bet_amount, 0)) as total_bet,
//             SUM(COALESCE(prize_amount, 0)) as total_payout,
//             SUM(COALESCE(prize_amount, 0) - COALESCE(bet_amount, 0)) as win_lose
//         ')
//         ->groupBy('player_id')
//         ->get();

//     // Attach player and agent info
//     $report = $report->map(function ($row) {
//         $player = User::find($row->player_id);
//         $row->player_user_name = $player?->user_name;
//         $row->agent_user_name = $player?->agent?->user_name;
//         return $row;
//     });

//     // Totals
//     $totals = [
//         'total_bet'    => $report->sum('total_bet'),
//         'total_payout' => $report->sum('total_payout'),
//         'win_lose'     => $report->sum('win_lose'),
//     ];

//     return view('admin.report.player_report_index', [
//         'report' => $report,
//         'totals' => $totals,
//     ]);
// }

    // public function summary(Request $request)
    // {
    //     $auth = Auth::user();

    //     // Fetch all descendant players (direct + subagent players)
    //     $players = $auth->getAllDescendantPlayers();

    //     // Player IDs array for PlaceBet filter
    //     $playerIds = $players->pluck('id')->toArray();



    //     $start = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
    //     $end = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

    //     $placeBets = PlaceBet::query()
    //         ->whereIn('player_id', $playerIds);

    //     if ($start) {
    //         $placeBets->where('created_at', '>=', $start.' 00:00:00');
    //     }
    //     if ($end) {
    //         $placeBets->where('created_at', '<=', $end.' 23:59:59');
    //     }

    //     if ($request->filled('member_account')) {
    //         $placeBets->where('member_account', $request->member_account);
    //     }
        


    //     // Group by player
    //     $report = $placeBets
    //         ->selectRaw('
    //             player_id,
    //             COUNT(id) as total_spins,
    //             SUM(COALESCE(bet_amount, 0)) as total_bet,
    //             SUM(COALESCE(prize_amount, 0)) as total_payout,
    //             SUM(COALESCE(prize_amount, 0) - COALESCE(bet_amount, 0)) as win_lose
    //         ')
    //         ->groupBy('player_id')
    //         ->get();

    //     // Attach player and agent info
    //     $report = $report->map(function ($row) {
    //         $player = User::find($row->player_id);
    //         $row->player_user_name = $player?->user_name;
    //         $row->agent_user_name = $player?->agent?->user_name;
    //         return $row;
    //     });

    //     // Totals
    //     $totals = [
    //         'total_bet'    => $report->sum('total_bet'),
    //         'total_payout' => $report->sum('total_payout'),
    //         'win_lose'     => $report->sum('win_lose'),
    //     ];

    //     return view('admin.report.player_report_index', [
    //         'report' => $report,
    //         'totals' => $totals,
    //     ]);
    // }
}







