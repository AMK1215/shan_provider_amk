<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlaceBet;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Support\Facades\Auth;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function report(Request $request)
{
    $user = auth()->user();
    $summary = app(ReportService::class)->getPlayerSummary($user);

    return view('admin.report.summary', compact('summary'));
}

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PlaceBet::query();

        // Owner can see all reports without restrictions
        if ($user->user_type !== UserType::Owner->value) {
            if ($user->user_type === UserType::Master->value) {
                // Master: see all bets where player_agent_id is in their agents' ids
                $agentIds = User::where('agent_id', $user->id)->pluck('id');
                $query->whereIn('player_agent_id', $agentIds);
            } elseif ($user->user_type === UserType::Agent->value || $user->user_type === UserType::SubAgent->value) {
                // Agent/SubAgent: see all bets where player_agent_id is their own id
                $query->where('player_agent_id', $user->id);
            } elseif ($user->user_type === UserType::Player->value) {
                // Player: see only their own bets
                $query->where('player_id', $user->id);
            } else {
                // Default: restrict as needed
                $query->where('player_id', $user->id);
            }
        }

        // Filters
        if ($request->filled('member_account')) {
            $query->where('member_account', $request->member_account);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Player summary aggregates
        $summary = (clone $query)
            ->selectRaw('
                member_account, 
                COUNT(*) as stake_count, 
                COALESCE(SUM(bet_amount), SUM(amount), 0) as total_stake, 
                COALESCE(SUM(bet_amount), SUM(amount), 0) as total_bet, 
                COALESCE(SUM(CASE WHEN prize_amount > 0 THEN prize_amount ELSE 0 END), 0) as total_win, 
                COALESCE(SUM(CASE WHEN prize_amount <= 0 THEN COALESCE(bet_amount, amount) ELSE 0 END), 0) as total_lose
            ')
            ->groupBy('member_account')
            ->get();

        return view('admin.report.index', compact('summary'));
    }

    public function show(Request $request, $member_account)
    {
        $user = Auth::user();
        $query = PlaceBet::query()->where('member_account', $member_account);

        // Get the player user record
        $player = User::where('user_name', $member_account)->first();
        if (!$player) {
            abort(404, 'Player not found');
        }

        if ($user->user_type === UserType::Owner->value) {
            // Owner: see all
        } elseif ($user->user_type === UserType::Master->value) {
            // Master: see bets for players under their agents
            $agentIds = User::where('agent_id', $user->id)->pluck('id');
            $subAgentIds = User::whereIn('agent_id', $agentIds)->pluck('id');
            $playerIds = User::whereIn('agent_id', $subAgentIds)->pluck('id');
            
            if (!$playerIds->contains($player->id)) {
                abort(403, 'Unauthorized access to player data');
            }
        } elseif ($user->user_type === UserType::Agent->value) {
            // Agent: see bets for players under their sub-agents
            $subAgentIds = User::where('agent_id', $user->id)->pluck('id');
            $playerIds = User::whereIn('agent_id', $subAgentIds)->pluck('id');
            
            if (!$playerIds->contains($player->id)) {
                abort(403, 'Unauthorized access to player data');
            }
        } elseif ($user->user_type === UserType::SubAgent->value) {
            // SubAgent: see bets only for their direct players
            $playerIds = User::where('agent_id', $user->id)->pluck('id');
            
            if (!$playerIds->contains($player->id)) {
                abort(403, 'Unauthorized access to player data');
            }
        } elseif ($user->user_type === UserType::Player->value) {
            // Player: see only their own bets
            if ($user->id !== $player->id) {
                abort(403, 'Unauthorized access to player data');
            }
        } else {
            abort(403, 'Unauthorized access');
        }

        $bets = $query->orderByDesc('created_at')->paginate(50);

        return view('admin.report.show', compact('bets', 'member_account'));
    }
} 