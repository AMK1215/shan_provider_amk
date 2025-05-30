<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\User;
use App\Models\WithDrawRequest;
use App\Services\WalletService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class DepositRequestController extends Controller
{
    protected const SUB_AGENT_ROLE = 'SubAgent';

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSubAgent = $user->hasRole(self::SUB_AGENT_ROLE);
        $agent = $isSubAgent ? $user->agent : $user;

        $startDate = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        $deposits = DepositRequest::with(['user', 'bank', 'agent'])
            ->where('agent_id', $agent->id)
            ->when($isSubAgent, function ($query) use ($user) {
                $query->where('sub_agent_id', $user->id);
            })
            ->when($request->filled('status') && $request->input('status') !== 'all', function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->orderBy('id', 'desc')
            ->get();

        $totalDeposits = $deposits->sum('amount');

        return view('admin.deposit_request.index', compact('deposits', 'totalDeposits', 'isSubAgent'));
    }

    public function statusChangeIndex(Request $request, DepositRequest $deposit)
    {
        try {
            $user = Auth::user();
            $isSubAgent = $user->hasRole(self::SUB_AGENT_ROLE);
            $agent = $isSubAgent ? $user->agent : $user;

            // Check if user has permission to handle this deposit
            if ($deposit->agent_id !== $agent->id || 
                ($isSubAgent && $deposit->sub_agent_id !== $user->id)) {
                return redirect()->back()->with('error', 'You do not have permission to handle this deposit request!');
            }

            $player = User::find($request->player);

            if ($request->status == 1 && $agent->balanceFloat < $request->amount) {
                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            $note = 'Deposit request approved by ' . $user->user_name . ' on ' . Carbon::now()->timezone('Asia/Yangon')->format('d-m-Y H:i:s');

            $deposit->update([
                'status' => $request->status,
                'note' => $note,
                'sub_agent_id' => $user->id,
                'sub_agent_name' => $user->user_name,
            ]);

            if ($request->status == 1) {
                app(WalletService::class)->transfer($agent, $player, $request->amount,
                    TransactionName::DebitTransfer, [
                        'old_balance' => $player->balanceFloat,
                        'new_balance' => $player->balanceFloat + $request->amount,
                    ]
                );
            }

            return redirect()->route('admin.agent.deposit')->with('success', 'Deposit status updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function statusChangeReject(Request $request, DepositRequest $deposit)
    {
        $request->validate([
            'status' => 'required|in:0,1,2',
        ]);

        try {
            $user = Auth::user();
            $isSubAgent = $user->hasRole(self::SUB_AGENT_ROLE);
            $agent = $isSubAgent ? $user->agent : $user;

            // Check if user has permission to handle this deposit
            if ($deposit->agent_id !== $agent->id || 
                ($isSubAgent && $deposit->sub_agent_id !== $user->id)) {
                return redirect()->back()->with('error', 'You do not have permission to handle this deposit request!');
            }

            $note = 'Deposit request rejected by ' . $user->user_name . ' on ' . Carbon::now()->timezone('Asia/Yangon')->format('d-m-Y H:i:s');

            $deposit->update([
                'status' => $request->status,
                'note' => $request->note,
                'sub_agent_id' => $user->id,
                'sub_agent_name' => $user->user_name,
            ]);

            return redirect()->route('admin.agent.deposit')->with('success', 'Deposit status updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function view(DepositRequest $deposit)
    {
        $user = Auth::user();
        $isSubAgent = $user->hasRole(self::SUB_AGENT_ROLE);
        $agent = $isSubAgent ? $user->agent : $user;

        // Check if user has permission to view this deposit
        if ($deposit->agent_id !== $agent->id || 
            ($isSubAgent && $deposit->sub_agent_id !== $user->id)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view this deposit request!');
        }

        return view('admin.deposit_request.view', compact('deposit', 'isSubAgent'));
    }

    private function isExistingAgent($userId)
    {
        $user = User::find($userId);
        return $user && $user->hasRole(self::SUB_AGENT_ROLE) ? $user->parent : null;
    }

    private function getAgent()
    {
        return $this->isExistingAgent(Auth::id());
    }
}
