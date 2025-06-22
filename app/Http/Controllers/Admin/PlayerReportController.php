<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlaceBet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\UserType;



class PlayerReportController extends Controller
{

    // public function summary(Request $request)
    // {
    //     $auth = Auth::user();
    //     $playerIds = collect(); // Initialize an empty collection for player IDs

    //     // Determine which players to display based on the authenticated user's role (type)
    //     if ($auth->type === UserType::Owner) {
    //         // Owner can see all players in the system
    //         $playerIds = User::where('type', UserType::Player)->pluck('id')->toArray();

    //     } elseif ($auth->type === UserType::Agent) {
    //         // Agent can see all players directly under them and all players under their subagents.
    //         // getAllDescendantPlayers should correctly gather players from subagents if they are linked to the agent.
    //         $players = $auth->getAllDescendantPlayers();
    //         $playerIds = $players->pluck('id')->toArray();

    //     } elseif ($auth->type === UserType::SubAgent) {
    //         // SubAgent should see players under their direct parent agent.
    //         $parentAgent = $auth->agent->parent; // Get the subagent's direct parent agent

    //         if ($parentAgent) {
    //             // Get all players that have this parentAgent's ID as their agent_id
    //             $playersUnderParentAgent = User::where('agent_id', $parentAgent->id)
    //                                          ->where('type', UserType::Player)
    //                                          ->pluck('id')
    //                                          ->toArray();
    //             $playerIds = $playersUnderParentAgent;
    //         } else {
    //             $playerIds = []; // No players to show if subagent has no parent
    //         }
    //     } elseif ($auth->type === UserType::Player) {
    //         // A player can only see their own report
    //         $playerIds = [$auth->id];
    //     }
    //     // If other user types (e.g., SystemWallet) log in, $playerIds will remain empty, showing no data.

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
    //         // Assuming 'player' relationship exists on PlaceBet and 'user_name' is on User model
    //         $placeBets->whereHas('player', function($query) use ($request) {
    //             $query->where('user_name', $request->member_account);
    //         });
    //         // If member_account is directly on PlaceBet, then:
    //         // $placeBets->where('member_account', $request->member_account);
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
    //         // The agent of the player is the one whose ID is in player->agent_id
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


    // working
    public function summary(Request $request)
{
    $auth = Auth::user();
    
    // subagent belong to parent agent_id and player belong to parent agent_id

    $playerIds = $auth->getAllDescendantPlayers()->pluck('id')->toArray();
    //$subAgentIds = $auth->getAllDescendantPlayers()->pluck('id')->toArray();

    $start = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
    $end = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

    // if($auth->hasRole(UserType::SubAgent->value)){
    //     $playerIds = $auth->getAllDescendantPlayers()->pluck('id')->toArray();
    // }else{
    //     $playerIds = [$auth->id];
    // }



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
        $placeBets->where('member_account', $request->member_account);
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







