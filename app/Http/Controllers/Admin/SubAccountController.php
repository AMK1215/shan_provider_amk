<?php

namespace App\Http\Controllers\Admin;

use Amp\Parallel\Worker\Execution;
use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferLogRequest;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\TransferLog;
use App\Models\User;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// use App\Models\PlaceBet;

class SubAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected const SUB_AGENT_ROLE = 3;

    // protected const SUB_AGENT_PROFILE = 'subagent_permission';
    protected const SUB_AGENT_PERMISSIONS = [
        'subagent_access',
        'player_view',
        'player_create',
        'withdraw',
        'deposit'
    ];

    public function index()
    {
        $users = User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->where('role_id', self::SUB_AGENT_ROLE);
            })
            ->where('agent_id', auth()->id())
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.sub_acc.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $agent_name = $this->generateRandomString();

        return view('admin.sub_acc.create', compact('agent_name'));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        try {
            $agent = User::create([
                'user_name' => $request->user_name,
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'type' => UserType::SubAgent,
                'agent_id' => Auth::id(),
            ]);
            $agent->roles()->sync(self::SUB_AGENT_ROLE);

            // Assign multiple permissions by title
            $permissionIds = \App\Models\Admin\Permission::whereIn('title', self::SUB_AGENT_PERMISSIONS)->pluck('id')->toArray();
            $agent->permissions()->sync($permissionIds);
        } catch (Exception $e) {
            Log::error('Error creating sub-agent: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to create sub-agent. Please try again.');
        }

        return redirect()->route('admin.subacc.index')->with('success', 'Sub-agent created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::find($id);

        return view('admin.sub_acc.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return redirect()->route('admin.subacc.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function banSubAcc($id)
    {
        $user = User::find($id);
        $user->update(['status' => $user->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            'User '.($user->status == 1 ? 'activate' : 'inactive').' successfully'
        );
    }

    public function getChangePassword($id)
    {
        $agent = User::find($id);

        return view('admin.sub_acc.change_password', compact('agent'));
    }

    public function makeChangePassword($id, Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $agent = User::find($id);
        $agent->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.subacc.index')
            ->with('successMessage', 'Agent Change Password successfully')
            ->with('password', $request->password)
            ->with('username', $agent->user_name);
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'SUBAG'.$randomNumber;
    }

    

    public function permission($id)
    {
        $subAgent = User::findOrFail($id);

        // Ensure the current user is the parent agent
        if ($subAgent->agent_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this sub-agent.');
        }

        // Only permissions in the 'subagent' group
        $permissions = \App\Models\Admin\Permission::where('group', 'subagent')->get()->groupBy('group');
        $subAgentPermissions = $subAgent->permissions->pluck('id')->toArray();

        return view('admin.sub_acc.sub_acc_permission', compact('subAgent', 'permissions', 'subAgentPermissions'));
    }

    public function updatePermission(Request $request, $id)
    {
        $subAgent = User::findOrFail($id);

        // Ensure the current user is the parent agent
        if ($subAgent->agent_id !== Auth::id()) {
            abort(403, 'You do not have permission to manage this sub-agent.');
        }

        $permissions = $request->input('permissions', []);
        $subAgent->permissions()->sync($permissions);

        return redirect()->back()->with('success', 'Permissions updated successfully.');
    }

    // sub agent profile
    public function subAgentProfile($id)
    {
        $subAgent = User::findOrFail($id);

        return view('admin.sub_acc.sub_acc_profile', compact('subAgent'));
    }

    

    public function agentPlayers(Request $request)
    {
        $subAgent = auth()->user();

        if (! $subAgent->hasRole('SubAgent')) {
            abort(403, 'Only subagents can access this page.');
        }

        $agent = $subAgent->agent;

        $query = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('title', 'Player');
        })
            ->where('agent_id', $agent->id);

        // Search by name or username
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%$search%")
                    ->orWhere('user_name', 'ILIKE', "%$search%")
                    ->orWhere('phone', 'ILIKE', "%$search%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $players = $query->orderBy('id', 'desc')->paginate(10)->appends($request->all());

        // For each player, get their totals
        foreach ($players as $player) {
            $totals = \App\Models\PlaceBet::where('member_account', $player->user_name)
                ->selectRaw('
                COUNT(id) as total_stake,
                SUM(bet_amount) as total_bet,
                SUM(prize_amount) as total_payout,
                MIN(before_balance) as min_before_balance,
                MAX(balance) as max_balance
            ')
                ->first();

            $player->total_stake = $totals->total_stake ?? 0;
            $player->total_bet = $totals->total_bet ?? 0;
            $player->total_payout = $totals->total_payout ?? 0;
            $player->min_before_balance = $totals->min_before_balance ?? 0;
            $player->max_balance = $totals->max_balance ?? 0;
        }

        return view('admin.sub_acc.agent_players', compact('players', 'agent'));
    }

    public function playerReport(Request $request, $id)
    {
        $player = \App\Models\User::findOrFail($id);

        $query = \App\Models\PlaceBet::where('member_account', $player->user_name);

        // Robust provider_name filter (case-insensitive, trimmed)
        if ($request->filled('provider_name')) {
            $query->whereRaw('LOWER(TRIM(provider_name)) = ?', [strtolower(trim($request->provider_name))]);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $bets = $query->orderBy('created_at', 'desc')->get();

        // Totals
        $total_stake = $bets->where('action', 'BET')->count();
        $total_bet = $bets->where('action', 'BET')->sum('bet_amount');
        //$total_bet = $bets->sum('bet_amount');
        $total_win = $bets->where('wager_status', 'SETTLED')->sum('prize_amount');
        $total_lost = $total_bet - $total_win;
        $net_win = $total_win - $total_bet; // Positive if won, negative if lost

        $is_win = $net_win > 0;
        $is_lost = $net_win < 0;

        // Provider dropdown
        $providers = \App\Models\PlaceBet::where('member_account', $player->user_name)
            ->select('provider_name')
            ->distinct()
            ->pluck('provider_name');

        return view('admin.sub_acc.player_report_detail', compact(
            'player', 'bets', 'total_stake', 'total_bet', 'total_win', 'total_lost', 'providers', 'net_win', 'is_win', 'is_lost'
        ));
    }

    // ----------------- Transaction method -------------
    public function getCashIn(User $player)
    {
        abort_if(
            Gate::denies('subagent_deposit'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $subAgent = Auth::user();
        $agent = $subAgent->agent;

        return view('admin.sub_acc.cash_in', compact('player', 'agent'));
    }

    public function makeCashIn(TransferLogRequest $request, User $player)
    {
        abort_if(
            Gate::denies('subagent_deposit'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            DB::beginTransaction();
            $inputs = $request->validated();
            $inputs['refrence_id'] = $this->getRefrenceId();

            $subAgent = Auth::user();      // The subagent making the request
            $agent = $subAgent->agent;     // The parent agent (who owns the balance)

            $cashIn = $inputs['amount'];

            if ($cashIn > $agent->balanceFloat) {

                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            app(WalletService::class)->transfer($agent, $player, $request->validated('amount'),
                TransactionName::CreditTransfer, [
                    'note' => $request->note,
                    'old_balance' => $player->balanceFloat,
                    'new_balance' => $player->balanceFloat + $request->amount,
                ]);
            // Log the transfer
            TransferLog::create([
                'from_user_id' => $agent->id,
                'to_user_id' => $player->id,
                'sub_agent_id' => $subAgent->id,
                'sub_agent_name' => $subAgent->user_name,
                'amount' => $request->amount,
                'type' => 'top_up',
                'description' => 'TopUp transfer from '.$agent->user_name.' to player',
                'meta' => [
                    'transaction_type' => TransactionName::Deposit->value,
                    'note' => $request->note,
                    'old_balance' => $player->balanceFloat,
                    'new_balance' => $player->balanceFloat + $request->amount,
                ],
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'CashIn submitted successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function getCashOut(User $player)
    {
        abort_if(
            Gate::denies('subagent_withdraw'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $subAgent = Auth::user();
        $agent = $subAgent->agent;

        return view('admin.sub_acc.cash_out', compact('player', 'agent'));
    }

    public function makeCashOut(TransferLogRequest $request, User $player)
    {
        abort_if(
            Gate::denies('subagent_withdraw'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            DB::beginTransaction();
            $inputs = $request->validated();
            $inputs['refrence_id'] = $this->getRefrenceId();

            $subAgent = Auth::user();      // The subagent making the request
            $agent = $subAgent->agent;     // The parent agent (who owns the balance)

            $cashOut = $inputs['amount'];

            if ($cashOut > $player->balanceFloat) {

                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            app(WalletService::class)->transfer($player, $agent, $request->validated('amount'),
                TransactionName::DebitTransfer, [
                    'note' => $request->note,
                    'old_balance' => $player->balanceFloat,
                    'new_balance' => $player->balanceFloat - $request->amount,
                ]);
            // Log the transfer
            TransferLog::create([
                'from_user_id' => $player->id,
                'to_user_id' => $agent->id,
                'sub_agent_id' => $subAgent->id,
                'sub_agent_name' => $subAgent->user_name,
                'amount' => $request->amount,
                'type' => 'withdraw',
                'description' => 'Credit transfer from player to '.$agent->user_name,
                'meta' => [
                    'transaction_type' => TransactionName::Withdraw->value,
                    'note' => $request->note,
                    'old_balance' => $player->balanceFloat,
                    'new_balance' => $player->balanceFloat - $request->amount,
                ],
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'CashOut submitted successfully!');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function getRefrenceId($prefix = 'REF')
    {
        return uniqid($prefix);
    }

    // transfer log
    public function SubAgentTransferLog(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('SubAgent')) {
            $query = TransferLog::with(['fromUser', 'toUser'])
                ->where('sub_agent_id', $user->id);
        } else {
            // Get subagent IDs for this agent
            $subAgentIds = User::where('agent_id', $user->id)
                ->whereHas('roles', function ($q) {
                    $q->where('title', 'SubAgent');
                })->pluck('id')->toArray();

            $query = TransferLog::with(['fromUser', 'toUser'])
                ->where(function ($q) use ($user, $subAgentIds) {
                    $q->where('from_user_id', $user->id)
                        ->orWhereIn('sub_agent_id', $subAgentIds);
                });
        }

        // Apply filters if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $transferLogs = $query->latest()->paginate(20);

        return view('admin.transfer_logs.subacc_log', compact('transferLogs'));
    }
}
