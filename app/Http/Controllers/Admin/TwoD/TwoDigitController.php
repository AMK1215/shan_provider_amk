<?php

namespace App\Http\Controllers\Admin\TwoD;

use App\Http\Controllers\Controller;
use App\Models\TwoDigit\Bettle;
use App\Models\TwoDigit\ChooseDigit;
use App\Models\TwoDigit\HeadClose;
use App\Models\TwoDigit\TwoDLimit; // Import JsonResponse
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TwoDigit\TwoBet;
use App\Models\TwoDigit\TwoBetSlip;
use App\Models\TwoDigit\TwoDResult;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TwoDigitController extends Controller
{
    // 2d report
    public function index(Request $request)
{
    $session = $request->input('session'); // 'morning'/'evening'
    $date = $request->input('date') ?? now()->toDateString();

    $user = auth()->user();

    if ($user->hasRole('Owner')) {
        $bets = TwoBet::where('session', $session)
            ->where('game_date', $date)
            ->with(['user', 'agent'])
            ->get();
    } elseif ($user->hasRole('Agent')) {
        $playerIds = $user->getAllDescendantPlayers()->pluck('id');
        $bets = TwoBet::whereIn('user_id', $playerIds)
            ->where('session', $session)
            ->where('game_date', $date)
            ->with('user')
            ->get();
    } else {
        // Player
        $bets = TwoBet::where('user_id', $user->id)
            ->where('session', $session)
            ->where('game_date', $date)
            ->get();
    }

    return view('admin.two_digit.report.index', compact('bets'));
}

public function betSlipList(Request $request)
{
    $session = $request->input('session', 'morning'); // or get from dropdown
    $date = $request->input('date', now()->toDateString());

    $query = \App\Models\TwoDigit\TwoBetSlip::where('session', $session)
        ->whereDate('created_at', $date);

    // Optional: filter for agent/owner role
    if (auth()->user()->hasRole('Agent')) {
        // Only agent's players
        $playerIds = auth()->user()->getAllDescendantPlayers()->pluck('id');
        $query->whereIn('user_id', $playerIds);
    }

    $slips = $query->latest()->paginate(30); // Or get(), or datatables

    return view('admin.two_digit.report.index', compact('slips'));
}

public function betSlipDetails($slip_id)
{
    $user = auth()->user();

    // Fetch the slip (with user/agent relationships if needed)
    $slip = TwoBetSlip::with('user')->findOrFail($slip_id);

    // Only allow owner, or the agent whose player placed this slip, or the player himself
    if ($user->hasRole('Owner')) {
        // Owner can see all
    } elseif ($user->hasRole('Agent')) {
        // Agent: Only see their own players' slips
        $agentPlayerIds = $user->getAllDescendantPlayers()->pluck('id')->toArray();
        if (!in_array($slip->user_id, $agentPlayerIds)) {
            abort(403, 'Unauthorized');
        }
    } elseif ($user->id != $slip->user_id) {
        // Player: Only own slips
        abort(403, 'Unauthorized');
    }

    // Fetch all bets for this slip, with player info
    $bets = TwoBet::where('slip_id', $slip->id)
        ->with('user')
        ->orderBy('id')
        ->get();

    // Return Blade partial for AJAX load
    return view('admin.two_digit.report.details', compact('bets', 'slip'));
}


    // head close digit
    public function headCloseDigit()
    {
        // get all head close digit
        $headCloseDigits = HeadClose::orderBy('head_close_digit', 'asc')->get();
        // get all choose close digit, ordered by choose_close_digit
        $chooseCloseDigits = ChooseDigit::orderBy('choose_close_digit', 'asc')->get();
        $battles = Bettle::orderBy('start_time', 'asc')->get();
        // get lasted first two d limit
        $twoDLimit = TwoDLimit::orderBy('created_at', 'desc')->first();
        // get last two d result
        $twoDResult = TwoDResult::orderBy('created_at', 'desc')->first();

        return view('admin.two_digit.close_digit.index', compact('headCloseDigits', 'chooseCloseDigits', 'battles', 'twoDLimit', 'twoDResult'));
    }

    // choose close digit
    public function chooseCloseDigit()
    {
        $chooseCloseDigits = ChooseDigit::all();

        return view('admin.two_digit.close_digit.index', compact('chooseCloseDigits'));
    }

    // toggle status
    public function toggleStatus(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'id' => 'required|integer|exists:choose_digits,id', // Ensure ID is valid and exists
            'status' => 'required|integer|in:0,1', // Ensure status is 0 or 1
        ]);

        try {
            $digit = HeadClose::find($request->id);

            if (! $digit) {
                return response()->json(['success' => false, 'message' => 'Digit not found.'], 404);
            }

            $digit->status = $request->status;
            $digit->save();

            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to toggle ChooseDigit status: '.$e->getMessage(), [
                'digit_id' => $request->id,
                'requested_status' => $request->status,
                'exception' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    // toggle choose digit status
    /**
     * Toggles the status of a ChooseDigit record.
     */
    public function toggleChooseDigitStatus(Request $request): JsonResponse
    {
        // Validate incoming request data
        $request->validate([
            'id' => 'required|integer|exists:choose_digits,id', // Ensure ID is valid and exists
            'status' => 'required|integer|in:0,1', // Ensure status is 0 or 1
        ]);

        try {
            $digit = ChooseDigit::find($request->id);

            if (! $digit) {
                return response()->json(['success' => false, 'message' => 'Digit not found.'], 404);
            }

            $digit->status = $request->status;
            $digit->save();

            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to toggle ChooseDigit status: '.$e->getMessage(), [
                'digit_id' => $request->id,
                'requested_status' => $request->status,
                'exception' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    /**
     * Toggles the status of a Battle record.
     */
    public function toggleBattleStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:battles,id', // Validate ID and existence in 'battles' table
            'status' => 'required|integer|in:0,1', // Validate status is 0 or 1
        ]);

        try {
            $battle = Bettle::find($request->id);

            if (! $battle) {
                return response()->json(['success' => false, 'message' => 'Battle time not found.'], 404);
            }

            $battle->status = $request->status;
            $battle->save();

            return response()->json(['success' => true, 'message' => 'Battle status updated successfully.']);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle Battle status: '.$e->getMessage(), [
                'battle_id' => $request->id,
                'requested_status' => $request->status,
                'exception' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    // store two d limit
    public function storeTwoDLimit(Request $request)
    {
        Log::info($request->all());
        $request->validate([
            'two_d_limit' => 'required|integer',
        ]);
        $twoDLimit = TwoDLimit::create([
            'two_d_limit' => $request->two_d_limit,
        ]);
        Log::info($twoDLimit);

        return redirect()->route('admin.head-close-digit')->with('success', 'TwoD Limit (Break) added successfully.');
    }

    // two d bet
    public function twoDBet()
    {
        $twoDBets = TwoDBet::all();

        return view('admin.two_digit.bet.index', compact('twoDBets'));
    }

    // store two d bet result
    
    
    public function storeTwoDResult(Request $request)
    {
        \Log::info('storeTwoDResult called', ['request' => $request->all()]);
        $request->validate([
            'win_number' => 'required|integer',
            'session' => 'required|string',
            'result_date' => 'required|date',
            'result_time' => 'required|date_format:H:i',
           
        ]);
    
        DB::transaction(function () use ($request) {
            // 1. Save the result
            $bettle = Bettle::where('status', 1)->first();
            $twoDResult = TwoDResult::create([
                'win_number' => $request->win_number,
                'session' => $request->session,
                'result_date' => $request->result_date,
                'result_time' => $request->result_time,
                'battle_id' => $bettle->id,
            ]);
            Log::info('TwoDResult created', ['twoDResult' => $twoDResult]);
    
            // 2. Find all bets for session/date
            $allBets = TwoBet::where('session', $request->session)
                ->where('game_date', $request->result_date)
                ->get();
            Log::info('Fetched bets', ['count' => $allBets->count()]);
    
            // 3. Process each bet
            foreach ($allBets as $bet) {
                $isWinner = $bet->bet_number == $request->win_number;
    
                if ($isWinner) {
                    $prize = $bet->bet_amount * 80;
    
                    // Update player wallet (users table, main_balance)
                    $player = User::find($bet->user_id);
                    if ($player) {
                        $player->main_balance += $prize;
                        $player->save();
                        Log::info('Prize added to player', ['user_id' => $player->id, 'prize' => $prize]);
                    }
    
                    // Update bet as win
                    $bet->win_lose = true;
                    $bet->potential_payout = $prize;
                    $bet->prize_sent = true;
                    Log::info('Bet marked as win', ['bet_id' => $bet->id, 'prize' => $prize]);
                } else {
                    // Update bet as lose
                    $bet->win_lose = false;
                    $bet->potential_payout = 0;
                    $bet->prize_sent = false;
                    Log::info('Bet marked as lose', ['bet_id' => $bet->id]);
                }
    
                // Update all common fields
                $bet->bet_status = true; // settled
                $bet->bet_result = $request->win_number;
                $bet->save();
            }
    
            // 4. Update all slips for this session/date to completed
            $updated = TwoBetSlip::where('session', $request->session)
                ->whereDate('created_at', $request->result_date)
                ->update(['status' => 'completed']);
            Log::info('Updated slips to completed', ['updated_count' => $updated]);
        });
    
        return redirect()->route('admin.head-close-digit')->with('success', 'TwoD Result added and winners paid.');
    }
    
}
