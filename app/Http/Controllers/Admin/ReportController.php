<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlaceBet;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PlaceBet::query();

        if ($user->user_type === UserType::Owner->value) {
            // Owner: see all
        } elseif ($user->user_type === UserType::Master->value) {
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

        $bets = $query->orderByDesc('created_at')->paginate(50);

        // Player summary aggregates
        $summary = (clone $query)
            ->selectRaw('member_account, COUNT(*) as stake_count, SUM(bet_amount) as total_stake, SUM(bet_amount) as total_bet, SUM(CASE WHEN prize_amount > 0 THEN prize_amount ELSE 0 END) as total_win, SUM(CASE WHEN prize_amount <= 0 THEN bet_amount ELSE 0 END) as total_lose')
            ->groupBy('member_account')
            ->get();

        return view('admin.report.index', compact('bets', 'summary'));
    }
} 