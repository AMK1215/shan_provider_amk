<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlaceBet;
use App\Models\User;
use App\Models\Product;
use App\Enums\UserType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $agent = $this->getAgent() ?? Auth::user();
        $report = $this->buildQuery($request, $agent);

        $totalstake = $report->sum('stake_count');
        $totalBetAmt = $report->sum('total_bet');
        $totalWinAmt = $report->sum('total_win');

        $total = [
            'totalstake' => $totalstake,
            'totalBetAmt' => $totalBetAmt,
            'totalWinAmt' => $totalWinAmt,
        ];

        return view('admin.report.index', compact('report', 'total'));
    }

    public function getReportDetails(Request $request, $member_account)
    {
        $user = Auth::user();
        $player = User::where('user_name', $member_account)->first();
        if (!$player) {
            abort(404, 'Player not found');
        }

        // Authorization logic (mirroring the show method)
        if ($user->user_type === UserType::Owner->value) {
            // Owner: see all
        } elseif ($user->user_type === UserType::Master->value) {
            $agentIds = User::where('agent_id', $user->id)->pluck('id');
            $subAgentIds = User::whereIn('agent_id', $agentIds)->pluck('id');
            $playerIds = User::whereIn('agent_id', $subAgentIds)->pluck('id');
            if (!$playerIds->contains($player->id)) {
                abort(403, 'Unauthorized access to player data');
            }
        } elseif ($user->user_type === UserType::Agent->value) {
            $subAgentIds = User::where('agent_id', $user->id)->pluck('id');
            $playerIds = User::whereIn('agent_id', $subAgentIds)->pluck('id');
            if (!$playerIds->contains($player->id)) {
                abort(403, 'Unauthorized access to player data');
            }
        } elseif ($user->user_type === UserType::SubAgent->value) {
            $playerIds = User::where('agent_id', $user->id)->pluck('id');
            if (!$playerIds->contains($player->id)) {
                abort(403, 'Unauthorized access to player data');
            }
        } elseif ($user->user_type === UserType::Player->value) {
            if ($user->id !== $player->id) {
                abort(403, 'Unauthorized access to player data');
            }
        } else {
            abort(403, 'Unauthorized access');
        }

        $details = $this->getPlayerDetails($member_account, $request);
        $productTypes = Product::where('status', 1)->get();

        return view('admin.report.detail', compact('details', 'productTypes', 'member_account'));
    }

    private function getAgent()
    {
        $user = Auth::user();
        // If you have a more complex agent lookup, implement here. For now, just return the user.
        return $user;
    }

    private function buildQuery(Request $request, $agent)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        $query = PlaceBet::query()
            ->select(
                'member_account',
                DB::raw('COUNT(*) as stake_count'),
                DB::raw('SUM(COALESCE(bet_amount, amount, 0)) as total_bet'),
                DB::raw('SUM(CASE WHEN prize_amount > 0 THEN prize_amount ELSE 0 END) as total_win')
            )
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // Apply agent/user hierarchy filtering here as needed
        if ($agent->user_type === UserType::Master->value) {
            $agentIds = User::where('agent_id', $agent->id)->pluck('id');
            $query->whereIn('player_agent_id', $agentIds);
        } elseif ($agent->user_type === UserType::Agent->value || $agent->user_type === UserType::SubAgent->value) {
            $query->where('player_agent_id', $agent->id);
        } elseif ($agent->user_type === UserType::Player->value) {
            $query->where('player_id', $agent->id);
        }

        if ($request->filled('member_account')) {
            $query->where('member_account', $request->member_account);
        }

        return $query->groupBy('member_account')->get();
    }

    private function getPlayerDetails($member_account, $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        return PlaceBet::where('member_account', $member_account)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->orderByDesc('created_at')
            ->get();
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