<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\PlaceBet;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $player = User::where('user_name', $member_account)->first();
        if (! $player) {
            abort(404, 'Player not found');
        }

        $details = $this->getPlayerDetails($member_account, $request);
        $productTypes = Product::where('status', 1)->get();

        return view('admin.report.detail', compact('details', 'productTypes', 'member_account'));
    }

    private function getAgent()
    {
        $user = Auth::user();

        return $user;
    }

    private function buildQuery(Request $request, $agent)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        $query = PlaceBet::query()
            ->select(
                'place_bets.member_account',
                'users.user_name as agent_name',
                DB::raw("COUNT(CASE WHEN action = 'BET' THEN 1 END) as stake_count"),
                DB::raw("SUM(CASE WHEN action = 'BET' THEN COALESCE(bet_amount, amount, 0) ELSE 0 END) as total_bet"),
                DB::raw("SUM(CASE WHEN wager_status = 'SETTLED' THEN prize_amount ELSE 0 END) as total_win")
            )
            ->leftJoin('users', 'place_bets.player_agent_id', '=', 'users.id')
            ->whereBetween('place_bets.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);

        // Apply agent/user hierarchy filtering based on role
        if ($agent->type === UserType::Owner->value) {
            // Owner can see all bets
            $query->whereNotNull('player_agent_id');
        } elseif ($agent->type === UserType::Agent->value) {
            // Agent can see their own bets and their SubAgents' and Players' bets
            $subAgentIds = User::where('agent_id', $agent->id)
                ->where('type', UserType::SubAgent->value)
                ->pluck('id');
            $playerIds = User::where('agent_id', $agent->id)
                ->where('type', UserType::Player->value)
                ->pluck('id');
            $query->whereIn('player_agent_id', $subAgentIds->merge($playerIds));
        } elseif ($agent->type === UserType::SubAgent->value) {
            // SubAgent can only see their Players' bets
            $playerIds = User::where('agent_id', $agent->id)
                ->where('type', UserType::Player->value)
                ->pluck('id');
            $query->whereIn('player_agent_id', $playerIds);
        } elseif ($agent->type === UserType::Player->value) {
            // Player can only see their own bets
            $query->where('player_id', $agent->id);
        }

        if ($request->filled('member_account')) {
            $query->where('member_account', $request->member_account);
        }

        return $query->groupBy('place_bets.member_account', 'users.user_name')->get();
    }

    private function getPlayerDetails($member_account, $request)
    {
        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        return PlaceBet::where('member_account', $member_account)
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function show(Request $request, $member_account)
    {
        $query = PlaceBet::query()->where('member_account', $member_account);

        // Get the player user record
        $player = User::where('user_name', $member_account)->first();
        if (! $player) {
            abort(404, 'Player not found');
        }

        $bets = $query->orderByDesc('created_at')->paginate(50);

        return view('admin.report.show', compact('bets', 'member_account'));
    }
}
