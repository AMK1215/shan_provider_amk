<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlayerRequest;
use App\Http\Requests\TransferLogRequest;
use App\Models\PaymentType;
use App\Models\Report;
use App\Models\TransferLog;
use App\Models\User;
use App\Services\UserService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PlayerController extends Controller
{
    protected $userService;

    private const PLAYER_ROLE = 4;

    protected const AGENT_ROLE = 'Agent';

    protected const SUB_AGENT_ROLE = 'SubAgent';

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */

     public function index()
{
    abort_if(
        Gate::denies('make_transfer'),
        Response::HTTP_FORBIDDEN,
        '403 Forbidden | You cannot access this page because you do not have permission'
    );

    // Step 1: Get all descendant player IDs under this owner/agent/subagent using recursive CTE
    $startId = auth()->id();

    $playerIds = collect(DB::select("
        WITH RECURSIVE descendants AS (
            SELECT id FROM users WHERE id = ?
            UNION ALL
            SELECT u.id FROM users u INNER JOIN descendants d ON u.agent_id = d.id
        )
        SELECT id FROM users WHERE id IN (SELECT id FROM descendants) AND type = '40'
    ", [$startId]))->pluck('id');

    // Step 2: Eager-load roles for the players, and whatever else you want
    $players = User::with(['roles', 'placeBets'])
        ->whereIn('id', $playerIds)
        ->select('id', 'name', 'user_name', 'phone', 'status', 'referral_code')
        ->orderBy('created_at', 'desc')
        ->get();

    // Step 3: PlaceBet stats (classic)
    // 3.1 Total spins (distinct spins, e.g. by wager_code)
    $spinTotals = \App\Models\PlaceBet::query()
        ->selectRaw('player_id, COUNT(DISTINCT wager_code) as total_spin')
        ->whereIn('player_id', $playerIds)
        ->groupBy('player_id')
        ->get()
        ->keyBy('player_id');

    // 3.2 Total bets (sum for BET)
    $betTotals = \App\Models\PlaceBet::query()
        ->selectRaw('player_id, SUM(bet_amount) as total_bet_amount')
        ->whereIn('player_id', $playerIds)
        ->where('wager_status', 'BET')
        ->groupBy('player_id')
        ->get()
        ->keyBy('player_id');

    // 3.3 Total payout (sum for SETTLED)
    $settleTotals = \App\Models\PlaceBet::query()
        ->selectRaw('player_id, SUM(prize_amount) as total_payout_amount')
        ->whereIn('player_id', $playerIds)
        ->where('wager_status', 'SETTLED')
        ->groupBy('player_id')
        ->get()
        ->keyBy('player_id');

    // Step 4: Build users collection with transfer logs for each player
    $users = $players->map(function ($player) use ($spinTotals, $betTotals, $settleTotals) {
        $spin = $spinTotals->get($player->id);
        $bet = $betTotals->get($player->id);
        $settle = $settleTotals->get($player->id);

        // Get transfer logs where this player is either from or to (eager-load fromUser and toUser)
        $logs = \App\Models\TransferLog::with(['fromUser', 'toUser'])
            ->where('from_user_id', $player->id)
            ->orWhere('to_user_id', $player->id)
            ->latest()
            ->get();

        return (object) [
            'id'                  => $player->id,
            'name'                => $player->name,
            'user_name'           => $player->user_name,
            'phone'               => $player->phone,
            'balanceFloat'        => $player->balanceFloat,
            'status'              => $player->status,
            'roles'               => $player->roles->pluck('name')->toArray(),
            'total_spin'          => $spin->total_spin ?? 0,
            'total_bet_amount'    => $bet->total_bet_amount ?? 0,
            'total_payout_amount' => $settle->total_payout_amount ?? 0,
            'logs'                => $logs,
        ];
    });

    return view('admin.player.index', compact('users'));
}


//      public function index()
// {
//     abort_if(
//         Gate::denies('make_transfer'),
//         Response::HTTP_FORBIDDEN,
//         '403 Forbidden | You cannot access this page because you do not have permission'
//     );

//     // Step 1: Get all descendant player IDs under this owner/agent/subagent using recursive CTE
//     $startId = auth()->id();

//     $playerIds = collect(DB::select("
//         WITH RECURSIVE descendants AS (
//             SELECT id FROM users WHERE id = ?
//             UNION ALL
//             SELECT u.id FROM users u INNER JOIN descendants d ON u.agent_id = d.id
//         )
//         SELECT id FROM users WHERE id IN (SELECT id FROM descendants) AND type = '40'
//     ", [$startId]))->pluck('id');

//     // Step 2: Eager-load roles for the players, and whatever else you want
//     $players = User::with(['roles', 'placeBets'])
//         ->whereIn('id', $playerIds)
//         ->select('id', 'name', 'user_name', 'phone', 'status', 'referral_code')
//         ->orderBy('created_at', 'desc')
//         ->get();

//     // Step 3: PlaceBet stats (classic)
//     // 3.1 Total spins (distinct spins, e.g. by wager_code)
//     $spinTotals = \App\Models\PlaceBet::query()
//         ->selectRaw('player_id, COUNT(DISTINCT wager_code) as total_spin')
//         ->whereIn('player_id', $playerIds)
//         ->groupBy('player_id')
//         ->get()
//         ->keyBy('player_id');

//     // 3.2 Total bets (sum for BET)
//     $betTotals = \App\Models\PlaceBet::query()
//         ->selectRaw('player_id, SUM(bet_amount) as total_bet_amount')
//         ->whereIn('player_id', $playerIds)
//         ->where('wager_status', 'BET')
//         ->groupBy('player_id')
//         ->get()
//         ->keyBy('player_id');

//     // 3.3 Total payout (sum for SETTLED)
//     $settleTotals = \App\Models\PlaceBet::query()
//         ->selectRaw('player_id, SUM(prize_amount) as total_payout_amount')
//         ->whereIn('player_id', $playerIds)
//         ->where('wager_status', 'SETTLED')
//         ->groupBy('player_id')
//         ->get()
//         ->keyBy('player_id');

//     // Step 4: Merge stats for output
//     $users = $players->map(function ($player) use ($spinTotals, $betTotals, $settleTotals) {
//         $spin = $spinTotals->get($player->id);
//         $bet = $betTotals->get($player->id);
//         $settle = $settleTotals->get($player->id);

//         return (object) [
//             'id'                  => $player->id,
//             'name'                => $player->name,
//             'user_name'           => $player->user_name,
//             'phone'               => $player->phone,
//             'balanceFloat'        => $player->balanceFloat,
//             'status'              => $player->status,
//             'roles'               => $player->roles->pluck('name')->toArray(),
//             'total_spin'          => $spin->total_spin ?? 0,
//             'total_bet_amount'    => $bet->total_bet_amount ?? 0,
//             'total_payout_amount' => $settle->total_payout_amount ?? 0,
//         ];
//     });

//     return view('admin.player.index', compact('users'));
// }

//     public function index()
// {
//     abort_if(
//         Gate::denies('make_transfer'),
//         Response::HTTP_FORBIDDEN,
//         '403 Forbidden | You cannot access this page because you do not have permission'
//     );

//     // 1. Get all relevant players (under this agent)
//     $players = User::with('roles')
//         ->whereHas('roles', fn($query) => $query->where('role_id', self::PLAYER_ROLE))
//         ->where('agent_id', auth()->id())
//         ->select('id', 'name', 'user_name', 'phone', 'status', 'referral_code')
//         ->orderBy('created_at', 'desc')
//         ->get();

//     $playerIds = $players->pluck('id');

//     // 2. Get total spins (all PlaceBet rows per player)
//     $spinTotals = \App\Models\PlaceBet::query()
//         ->selectRaw('player_id, COUNT(*) as total_spin')
//         ->whereIn('player_id', $playerIds)
//         ->groupBy('player_id')
//         ->get()
//         ->keyBy('player_id');

//     // 3. Get total bets (BET status)
//     $betTotals = \App\Models\PlaceBet::query()
//         ->selectRaw('player_id, SUM(bet_amount) as total_bet_amount')
//         ->whereIn('player_id', $playerIds)
//         ->where('wager_status', 'BET')
//         ->groupBy('player_id')
//         ->get()
//         ->keyBy('player_id');

//     // 4. Get total payouts (SETTLED status)
//     $settleTotals = \App\Models\PlaceBet::query()
//         ->selectRaw('player_id, SUM(prize_amount) as total_payout_amount')
//         ->whereIn('player_id', $playerIds)
//         ->where('wager_status', 'SETTLED')
//         ->groupBy('player_id')
//         ->get()
//         ->keyBy('player_id');

//     // 5. Merge for output
//     $users = $players->map(function ($player) use ($spinTotals, $betTotals, $settleTotals) {
//         $spin = $spinTotals->get($player->id);
//         $bet = $betTotals->get($player->id);
//         $settle = $settleTotals->get($player->id);

//         return (object) [
//             'id'                  => $player->id,
//             'name'                => $player->name,
//             'user_name'           => $player->user_name,
//             'phone'               => $player->phone,
//             'balanceFloat'        => $player->balanceFloat,
//             'status'              => $player->status,
//             'total_spin'          => $spin->total_spin ?? 0,                   // ALL spins
//             'total_bet_amount'    => $bet->total_bet_amount ?? 0,              // only BET
//             'total_payout_amount' => $settle->total_payout_amount ?? 0,        // only SETTLED
//         ];
//     });

//     return view('admin.player.index', compact('users'));
// }

    



    /**
     * Display a listing of the users with their agents.
     *
     * @return \Illuminate\View\View
     */
    public function player_with_agent()
    {
        $users = User::player()->with('roles')->get();

        return view('admin.player.list', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(
            Gate::denies('player_create'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $player_name = $this->generateRandomString();
        $agent = $this->getAgent() ?? Auth::user();
        // $owner_id = User::where('agent_id', $agent->agent_id)->first();
        // Get the related owner of the agent
        $owner = User::where('id', $agent->agent_id)->first(); // Assuming `agent_id` refers to the owner's ID

        // return $owner;

        return view('admin.player.create', compact('player_name', 'owner'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlayerRequest $request)
    {
        Gate::allows('player_store');

        $agent = $this->getAgent() ?? Auth::user();
        $siteLink = $agent->parent->parent->parent->site_link ?? 'null';

        $inputs = $request->validated();

        try {
            DB::beginTransaction();
            if (isset($inputs['amount']) && $inputs['amount'] > $agent->balanceFloat) {
                return redirect()->back()->with('error', 'Balance Insufficient');
            }

            $user = User::create([
                'name' => $inputs['name'],
                'user_name' => $inputs['user_name'],
                'password' => Hash::make($inputs['password']),
                'phone' => $inputs['phone'],
                'agent_id' => $agent->id,
                'type' => UserType::Player,
            ]);

            $user->roles()->sync(self::PLAYER_ROLE);

            if (isset($inputs['amount'])) {
                app(WalletService::class)->transfer($agent, $user, $inputs['amount'],
                    TransactionName::CreditTransfer, [
                        'old_balance' => $user->balanceFloat,
                        'new_balance' => $user->balanceFloat + $request->amount,
                    ]);
            }

            // Log the transfer
            TransferLog::create([
                'from_user_id' => $agent->id,
                'to_user_id' => $user->id,
                'amount' => $inputs['amount'],
                'type' => 'top_up',
                'description' => 'Initial Top Up from agent to new player',
                'meta' => [
                    'transaction_type' => TransactionName::CreditTransfer->value,
                    'old_balance' => $user->balanceFloat,
                    'new_balance' => $user->balanceFloat + $inputs['amount'],
                ],
            ]);

            DB::commit();

            return redirect()->back()
                ->with('successMessage', 'Player created successfully')
                ->with('amount', $request->amount)
                ->with('password', $request->password)
                ->with('site_link', $siteLink)
                ->with('user_name', $user->user_name);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while creating the player.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort_if(
            Gate::denies('player_show'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user_detail = User::findOrFail($id);

        return view('admin.player.show', compact('user_detail'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $player)
    {
        abort_if(
            Gate::denies('player_edit'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        return response()->view('admin.player.edit', compact('player'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $player)
    {

        $player->update($request->all());

        return redirect()->route('admin.player.index')->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $player)
    {
        abort_if(
            Gate::denies('player_delete') || ! $this->ifChildOfParent(request()->user()->id, $player->id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $player->delete();

        return redirect()->route('admin.player.index')->with('success', 'User deleted successfully');
    }

    public function massDestroy(Request $request)
    {
        User::whereIn('id', request('ids'))->delete();

        return response(null, 204);
    }

    public function banUser($id)
    {
        abort_if(
            ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user = User::find($id);
        $user->update(['status' => $user->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            'User '.($user->status == 1 ? 'activate' : 'inactive').' successfully'
        );
    }

    public function getCashIn(User $player)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        return view('admin.player.cash_in', compact('player'));
    }

    public function makeCashIn(TransferLogRequest $request, User $player)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            DB::beginTransaction();
            $inputs = $request->validated();
            $inputs['refrence_id'] = $this->getRefrenceId();

            $agent = $this->getAgent() ?? Auth::user();

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
                'amount' => $request->amount,
                'type' => 'deposit',
                'description' => 'Credit transfer from '.$agent->user_name.' to player',
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
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        return view('admin.player.cash_out', compact('player'));
    }

    public function makeCashOut(TransferLogRequest $request, User $player)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            DB::beginTransaction();
            $inputs = $request->validated();
            $inputs['refrence_id'] = $this->getRefrenceId();

            $agent = $this->getAgent() ?? Auth::user();

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

    public function getChangePassword($id)
    {
        $player = User::find($id);

        return view('admin.player.change_password', compact('player'));
    }

    public function makeChangePassword($id, Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $player = User::find($id);
        $player->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Player Change Password successfully')
            ->with('password', $request->password)
            ->with('username', $player->user_name);
    }

    public function playerReportIndex($id)
    {

        $startDate = request('start_date') ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = request('end_date') ?? Carbon::today()->endOfDay()->toDateString();

        $reportDetail = DB::table('reports')
            ->join('products', 'products.code', '=', 'reports.product_code')
            ->select(
                'reports.*', 'products.name as provider_name',
            )
            ->where('reports.member_name', $id)
            ->whereBetween('reports.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->paginate(20)
            ->appends([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $total = [
            'total_bet_amt' => $reportDetail->sum('bet_amount'),
            'total_payout_amt' => $reportDetail->sum('payout_amount'),
            'total_net_win' => $reportDetail->sum('payout_amount') - $reportDetail->sum('bet_amount'),
        ];

        return view('admin.player.report_index', compact('reportDetail', 'total'));
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'P'.$randomNumber;
    }

    private function getRefrenceId($prefix = 'REF')
    {
        return uniqid($prefix);
    }

    public function playersByAgent(Request $request, int $agentId)
    {
        $players = User::getPlayersByAgentId($agentId);

        return view('players.index', compact('players'));
    }

    private function isExistingUserForAgent($phone, $agent_id): bool
    {
        // return User::where('phone', $phone)->where('agent_id', $agent_id)->first();
        return User::where('phone', $phone)->where('agent_id', $agent_id)->exists();
    }

    private function isExistingAgent($userId)
    {
        $user = User::find($userId);
        // AGENT ROLE

        return $user && $user->hasRole(self::SUB_AGENT_ROLE) ? $user->parent : null;
    }

    // SUB AGENT ROLE
    private function isExistingSubAgent($userId)
    {
        $user = User::find($userId);
        // SUBAGENT ROLE

        return $user && $user->hasRole(self::AGENT_ROLE) ? $user->parent : null;
    }

    private function getAgent()
    {
        $user = User::find(Auth::id());
        if ($user->hasRole(self::AGENT_ROLE)) {
            return $this->isExistingAgent(Auth::id());
        } elseif ($user->hasRole(self::SUB_AGENT_ROLE)) {
            return $this->isExistingSubAgent(Auth::id());
        }

    }
}
