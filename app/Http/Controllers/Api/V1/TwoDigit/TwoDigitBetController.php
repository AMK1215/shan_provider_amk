<?php

namespace App\Http\Controllers\Api\V1\TwoDigit;

use App\Http\Controllers\Controller;
use App\Http\Requests\TwoD\TwoDPlayRequest;
use App\Models\TwoDigit\Bettle;
use App\Services\TwoDPlayService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request; // Ensure Auth facade is used
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import the service
use App\Models\TwoDigit\TwoBetSlip;

class TwoDigitBetController extends Controller
{
    use HttpResponses; // For success/error JSON responses

    protected TwoDPlayService $playService;

    public function __construct(TwoDPlayService $playService)
    {
        $this->playService = $playService;
    }

    /**
     * Store a new 2D bet.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TwoDPlayRequest $request)
    {
        Log::info('TwoDigitBetController: Store method called.');

        // 1. Authentication check (Laravel's auth middleware should handle this, but an explicit check is fine)
        if (! Auth::check()) {
            Log::warning('TwoDigitBetController: Unauthenticated attempt to place bet.');

            return $this->error('Authentication Required', 'You are not authenticated! Please login.', 401);
        }

        // 2. Check current active betting battle (session)
        $currentBettle = Bettle::where('status', true)->first();
        if (! $currentBettle) {
            Log::info('TwoDigitBetController: Betting is closed at this time.');

            return $this->error(
                'Betting Closed',
                'This 2D lottery Bettle is closed at this time. Welcome back next time!',
                401
            );
        }

        // Retrieve the validated data from the request
        $totalAmount = $request->input('totalAmount');
        $amounts = $request->input('amounts');

        Log::info('TwoDigitBetController: Validated amounts received', [
            'totalAmount' => $totalAmount,
            'amounts' => $amounts,
        ]);

        try {
            // Delegate the core betting logic to the TwoDPlayService
            $result = $this->playService->play($totalAmount, $amounts);

            // Handle different types of results from the service
            if (is_string($result)) {
                // If the service returns a string, it's an error message
                // This covers 'Insufficient funds.', 'Resource not found.', or general exceptions
                if ($result === 'Insufficient funds in your main balance.') {
                    return $this->error('Insufficient Funds', 'လက်ကျန်ငွေ မလုံလောက်ပါ။', 400); // 400 Bad Request for client-side issue
                } elseif ($result === 'Required resource (e.g., 2D Limit) not found.') {
                    return $this->error('Configuration Error', '2D limit configuration is missing. Please contact support.', 500);
                } elseif ($result === 'Betting is currently closed. No active battle session found.') {
                    // Although checked above, defensive check in service is good.
                    return $this->error('Betting Closed', 'This 2D lottery Bettle is closed at this time. Welcome back next time!', 401);
                } elseif ($result === 'Bet placed successfully.') {
                    return $this->success(null, 'ထီအောင်မြင်စွာ ထိုးပြီးပါပြီ။');
                } else {
                    // General service-side error
                    return $this->error('Betting Failed', $result, 400); // 400 or 500 depending on cause
                }
            } elseif (is_array($result) && ! empty($result)) {
                // If the service returns an array, it contains over-limit digits
                $digitStrings = collect($result)->map(fn ($digit) => "'{$digit}'")->implode(', ');
                $message = "သင့်ရွှေးချယ်ထားသော {$digitStrings} ဂဏန်းမှာ သတ်မှတ် အမောင့်ထက်ကျော်လွန်ပါသောကြောင့် ကံစမ်း၍မရနိုင်ပါ။";

                return $this->error('Over Limit', $message, 400); // 400 Bad Request
            } else {
                // Defensive fallback: treat as error
                return $this->error('Betting Failed', 'Unknown error occurred.', 400);
            }

        } catch (\Exception $e) {
            // Catch any unexpected exceptions from the service layer
            Log::error('TwoDigitBetController: Uncaught exception in store method: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $this->error('Server Error', 'An unexpected error occurred. Please try again later.', 500);
        }

        
    }

    // slip no
    public function myBetSlips(Request $request)
    {
       // $user = $request->user();
       $user = Auth::user();

        $betSlips = TwoBetSlip::with('twoBets')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('session', 'morning')
            ->where('game_date', now()->format('Y-m-d'))
            ->orderByDesc('created_at')
            ->get();

        return $this->success($betSlips, 'Your two-digit bet slips retrieved successfully.');
    }
}
