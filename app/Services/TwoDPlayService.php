<?php

namespace App\Services;

use App\Helpers\SessionHelper; // Assuming this helper correctly determines 'morning' or 'evening'
use App\Models\TwoDigit\Bettle;
use App\Models\TwoDigit\ChooseDigit;
use App\Models\TwoDigit\HeadClose;
use App\Models\TwoDigit\SlipNumberCounter;
use App\Models\TwoDigit\TwoBet; // Overall 2D limit
use App\Models\TwoDigit\TwoDLimit;
use App\Models\User; // Assuming User model is in App\Models
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Removed: use App\Services\WalletService; // No longer needed
// Removed: use App\Enums\DigitTransactionName; // No longer needed

class TwoDPlayService
{
    // Removed constructor as WalletService is no longer injected
    // public function __construct(WalletService $walletService)
    // {
    //     $this->walletService = $walletService;
    // }

    /**
     * Handles the logic for placing a 2D bet using custom main_balance.
     *
     * @param  float  $totalBetAmount  The total sum of all individual bet amounts.
     * @param  array  $amounts  An array of individual bets, e.g., [['num' => '01', 'amount' => 100], ...].
     * @return array|string Returns an array of over-limit digits, or a success message.
     *
     * @throws \Exception If authentication fails, limits are not set, or other issues occur.
     */
    public function play(float $totalBetAmount, array $amounts)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            // This should ideally be caught by middleware or auth checks before this service
            throw new \Exception('User not authenticated.');
        }

        // Check if the current betting session is active
        $currentBettle = Bettle::where('status', true)->first();
        if (! $currentBettle) {
            throw new \Exception('Betting is currently closed. No active battle session found.');
        }

        $sessionType = SessionHelper::getCurrentSession(); // 'morning' or 'evening'
        $gameDate = Carbon::now()->format('Y-m-d');
        $gameTime = $currentBettle->end_time; // Using battle's end_time for 'game_time' of the bet

        try {
            DB::beginTransaction();

            // 1. Get user's personal limit (if any) and overall 2D limit
            // Assuming 'two_d_limit' is a column directly on the User model for personal limit
            $userPersonalLimit = $user->two_d_limit ?? null;
            Log::info('User personal 2D limit: '.($userPersonalLimit ?? 'Not Set'));

            // Get the overall default 2D limit (break limit)
            $overallTwoDLimit = TwoDLimit::orderBy('created_at', 'desc')->first();
            if (! $overallTwoDLimit) {
                throw new ModelNotFoundException('Overall 2D limit (break) not set.');
            }
            $overallBreakAmount = $overallTwoDLimit->two_d_limit;
            Log::info("Overall 2D break limit: {$overallBreakAmount}");

            // 2. Check if user has sufficient funds using main_balance
            if ($user->main_balance < $totalBetAmount) {
                throw new \Exception('Insufficient funds in your main balance.');
            }

            // 3. Pre-check for over-limits (combining HeadClose and ChooseDigit checks)
            $overLimitDigits = $this->checkAllLimits($amounts, $sessionType, $gameDate, $overallBreakAmount, $userPersonalLimit);
            if (! empty($overLimitDigits)) {
                // No DB::rollBack() here, as this is a pre-check before any DB writes
                return $overLimitDigits; // Return the list of digits that are over limit
            }

            // All checks passed, proceed with deducting balance and creating bets
            $beforeBalance = $user->main_balance;

            // Deduct total amount from user's main_balance
            $user->main_balance -= $totalBetAmount;
            $user->save(); // Save the updated balance

            // Retrieve the balance after saving for the record
            $afterBalance = $user->main_balance;

            // Generate a unique slip number for the entire transaction batch
            $slipNo = $this->generateUniqueSlipNumber();

            foreach ($amounts as $betDetail) {
                $twoDigit = str_pad($betDetail['num'], 2, '0', STR_PAD_LEFT);
                $subAmount = $betDetail['amount'];

                // Get ChooseDigit, HeadClose (if applicable to record specific digit IDs)
                $chooseDigit = ChooseDigit::where('choose_close_digit', $twoDigit)->first();
                $headClose = HeadClose::where('head_close_digit', substr($twoDigit, 0, 1))->first();

                // Create the TwoBet record
                TwoBet::create([
                    'user_id' => $user->id,
                    'member_name' => $user->user_name, // Keeping as per your migration, but still redundant
                    'bettle_id' => $currentBettle->id,
                    'choose_digit_id' => $chooseDigit ? $chooseDigit->id : null,
                    'head_close_id' => $headClose ? $headClose->id : null,
                    'agent_id' => $user->agent_id, // Assuming user has an agent_id
                    'bet_number' => $twoDigit,
                    'bet_amount' => $subAmount,
                    'total_bet_amount' => $totalBetAmount, // Store total for the batch here, or individual bet amount
                    'session' => $sessionType, // Keeping as per your migration, but redundant
                    'win_lose' => false, // Initial state
                    'potential_payout' => 0, // Calculate this based on odds in real logic
                    'bet_status' => false, // Initial state: pending
                    'game_date' => $gameDate,
                    'game_time' => $gameTime,
                    'slip_no' => $slipNo,
                    'before_balance' => $beforeBalance, // Use beforeBalance from main_balance
                    'after_balance' => $afterBalance,   // Use afterBalance from main_balance
                ]);
            }

            DB::commit();

            return 'Bet placed successfully.';

        } catch (ModelNotFoundException $e) {
            DB::rollback();
            Log::error('Resource not found in TwoDPlayService: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return 'Required resource (e.g., 2D Limit) not found.';
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in TwoDPlayService play method: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $e->getMessage(); // Return specific error message for debugging
        }
    }

    /**
     * Checks all limits (head close, choose digit, and total bet amount per digit).
     *
     * @return array Returns an array of digits that are over limit.
     */
    protected function checkAllLimits(
        array $amounts,
        string $sessionType,
        string $gameDate,
        float $overallBreakAmount,
        ?float $userPersonalLimit // Nullable float
    ): array {
        $overLimitDigits = [];

        // Fetch closed digits and head digits outside the loop for efficiency
        $closedTwoDigits = ChooseDigit::where('status', false)
            ->pluck('choose_close_digit')
            ->map(fn ($digit) => str_pad($digit, 2, '0', STR_PAD_LEFT))
            ->unique()
            ->all();

        $closedHeadDigits = HeadClose::where('status', false)
            ->pluck('head_close_digit')
            ->map(fn ($digit) => (string) $digit) // Ensure string comparison
            ->unique()
            ->all();

        foreach ($amounts as $amount) {
            $twoDigit = str_pad($amount['num'], 2, '0', STR_PAD_LEFT);
            $subAmount = $amount['amount'];
            $headDigitOfSelected = substr($twoDigit, 0, 1);

            // Check if head digit is closed
            if (in_array($headDigitOfSelected, $closedHeadDigits)) {
                $overLimitDigits[] = $twoDigit;

                continue; // Move to next bet
            }

            // Check if full 2D digit is closed
            if (in_array($twoDigit, $closedTwoDigits)) {
                $overLimitDigits[] = $twoDigit;

                continue; // Move to next bet
            }

            // Check total bet amount for this digit against limits
            $totalBetAmountForTwoDigit = DB::table('two_bets') // Using 'two_bets' table
                ->where('game_date', $gameDate)
                ->where('session', $sessionType)
                ->where('bet_number', $twoDigit)
                ->sum('bet_amount'); // Sum 'bet_amount' from existing records

            // Calculate the new total if this bet is placed
            $projectedTotalBetAmount = $totalBetAmountForTwoDigit + $subAmount;

            // Check against overall 2D break limit
            if ($projectedTotalBetAmount > $overallBreakAmount) {
                $overLimitDigits[] = $twoDigit;

                continue; // Move to next bet
            }

            // If userPersonalLimit is defined, check against it (more restrictive)
            // This logic needs careful consideration: is userPersonalLimit a global break for the user,
            // or specific to how much *they* can bet on a single number?
            // Assuming it's how much *this user* can bet on *one specific digit*.
            $userBetAmountOnThisDigit = DB::table('two_bets')
                ->where('user_id', Auth::id())
                ->where('game_date', $gameDate)
                ->where('session', $sessionType)
                ->where('bet_number', $twoDigit)
                ->sum('bet_amount');

            $projectedUserBetAmount = $userBetAmountOnThisDigit + $subAmount;

            if ($userPersonalLimit !== null && $projectedUserBetAmount > $userPersonalLimit) {
                $overLimitDigits[] = $twoDigit;

                continue;
            }
        }

        return $overLimitDigits;
    }

    /**
     * Generates a unique slip number.
     */
    protected function generateUniqueSlipNumber(): string
    {
        $currentDate = Carbon::now()->format('Ymd'); // e.g., 20250628
        $currentTime = Carbon::now()->format('His'); // e.g., 143005
        $customString = 'mk-2d';

        // Get the current counter or create one if it doesn't exist
        $counter = SlipNumberCounter::firstOrCreate(['id' => 1], ['current_number' => 0]);
        // Increment the counter
        $counter->increment('current_number');
        $randomNumber = sprintf('%06d', $counter->current_number); // Ensure it's a 6-digit number with leading zeros

        return "{$customString}-{$currentDate}-{$currentTime}-{$randomNumber}";
    }
}
