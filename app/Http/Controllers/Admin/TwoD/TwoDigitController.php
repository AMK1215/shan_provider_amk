<?php

namespace App\Http\Controllers\Admin\TwoD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TwoDigit\HeadClose;
use App\Models\TwoDigit\ChooseDigit;
use Illuminate\Http\JsonResponse; // Import JsonResponse
use Illuminate\Support\Facades\Log;
use App\Models\TwoDigit\Bettle;
use App\Models\TwoDigit\TwoDLimit;


class TwoDigitController extends Controller
{
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
        return view('admin.two_digit.close_digit.index', compact('headCloseDigits', 'chooseCloseDigits', 'battles', 'twoDLimit'));
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

            if (!$digit) {
                return response()->json(['success' => false, 'message' => 'Digit not found.'], 404);
            }

            $digit->status = $request->status;
            $digit->save();

            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Failed to toggle ChooseDigit status: " . $e->getMessage(), [
                'digit_id' => $request->id,
                'requested_status' => $request->status,
                'exception' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    // toggle choose digit status
     /**
     * Toggles the status of a ChooseDigit record.
     *
     * @param Request $request
     * @return JsonResponse
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

            if (!$digit) {
                return response()->json(['success' => false, 'message' => 'Digit not found.'], 404);
            }

            $digit->status = $request->status;
            $digit->save();

            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Failed to toggle ChooseDigit status: " . $e->getMessage(), [
                'digit_id' => $request->id,
                'requested_status' => $request->status,
                'exception' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }


    /**
     * Toggles the status of a Battle record.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleBattleStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:battles,id', // Validate ID and existence in 'battles' table
            'status' => 'required|integer|in:0,1', // Validate status is 0 or 1
        ]);

        try {
            $battle = Bettle::find($request->id);

            if (!$battle) {
                return response()->json(['success' => false, 'message' => 'Battle time not found.'], 404);
            }

            $battle->status = $request->status;
            $battle->save();

            return response()->json(['success' => true, 'message' => 'Battle status updated successfully.']);

        } catch (\Exception $e) {
            \Log::error("Failed to toggle Battle status: " . $e->getMessage(), [
                'battle_id' => $request->id,
                'requested_status' => $request->status,
                'exception' => $e->getTraceAsString()
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

    // store two d bet

    
}
