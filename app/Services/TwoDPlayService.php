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

class TwoDPlayService
{
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
            throw new \Exception('User not authenticated.');
        }

        $currentBettle = Bettle::where('status', true)->first();
        if (! $currentBettle) {
            throw new \Exception('Betting is currently closed. No active battle session found.');
        }

        $sessionType = SessionHelper::getCurrentSession(); // 'morning' or 'evening'
        $gameDate = Carbon::now()->format('Y-m-d');
        $gameTime = $currentBettle->end_time; // Using battle's end_time for 'game_time' of the bet

        try {
            DB::beginTransaction();

            $userPersonalLimit = $user->two_d_limit ?? null;
            Log::info('User personal 2D limit: '.($userPersonalLimit ?? 'Not Set'));

            $overallTwoDLimit = TwoDLimit::orderBy('created_at', 'desc')->first();
            if (! $overallTwoDLimit) {
                throw new ModelNotFoundException('Overall 2D limit (break) not set.');
            }
            $overallBreakAmount = $overallTwoDLimit->two_d_limit;
            Log::info("Overall 2D break limit: {$overallBreakAmount}");

            if ($user->main_balance < $totalBetAmount) {
                throw new \Exception('Insufficient funds in your main balance.');
            }

            $overLimitDigits = $this->checkAllLimits($amounts, $sessionType, $gameDate, $overallBreakAmount, $userPersonalLimit);
            if (! empty($overLimitDigits)) {
                // No DB::rollBack() here, as this is a pre-check before any DB writes
                return $overLimitDigits;
            }

            // Generate a unique slip number ONCE for this entire batch of bets
            $slipNo = $this->generateUniqueSlipNumber();
            Log::info("Generated Slip No: {$slipNo}");


            $beforeBalance = $user->main_balance;

            // Deduct total amount from user's main_balance
            $user->main_balance -= $totalBetAmount;
            $user->save(); // Save the updated balance

            $afterBalance = $user->main_balance;

            foreach ($amounts as $betDetail) {
                $twoDigit = str_pad($betDetail['num'], 2, '0', STR_PAD_LEFT);
                $subAmount = $betDetail['amount'];

                $chooseDigit = ChooseDigit::where('choose_close_digit', $twoDigit)->first();
                $headClose = HeadClose::where('head_close_digit', substr($twoDigit, 0, 1))->first();

                // Create the TwoBet record
                TwoBet::create([
                    'user_id' => $user->id,
                    'member_name' => $user->user_name,
                    'bettle_id' => $currentBettle->id,
                    'choose_digit_id' => $chooseDigit ? $chooseDigit->id : null,
                    'head_close_id' => $headClose ? $headClose->id : null,
                    'agent_id' => $user->agent_id,
                    'bet_number' => $twoDigit,
                    'bet_amount' => $subAmount,
                    'total_bet_amount' => $totalBetAmount, // This should be total for the *entire slip*
                    'session' => $sessionType,
                    'win_lose' => false,
                    'potential_payout' => 0,
                    'bet_status' => false,
                    'game_date' => $gameDate,
                    'game_time' => $gameTime,
                    'slip_no' => $slipNo, // Use the single generated slip number for all bets in this batch
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
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

            return $e->getMessage();
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
     * This method ensures the slip number is unique across all *committed* two_bets records.
     * Since we generate it *before* the main transaction commits, the `exists()` check is reliable.
     */
    protected function generateUniqueSlipNumber(): string
    {
        $maxRetries = 10;
        $attempt = 0;
        
        do {
            $attempt++;
            // Generate the base slip number with the counter incremented
            $baseSlipNo = $this->generateBaseSlipNumberWithCounter();
            
            // Add microseconds to make it even more unique
            $microtime = microtime(true);
            $microseconds = sprintf('%06d', ($microtime - floor($microtime)) * 1000000);
            $slipNo = $baseSlipNo . '-' . $microseconds;
            
            // Check if this slip number already exists in the database
            // This check is outside the counter's transaction but inside the main play() transaction (before commit)
            // It ensures uniqueness against already committed records.
            $exists = DB::table('two_bets')->where('slip_no', $slipNo)->exists();
            
            if (!$exists) {
                return $slipNo;
            }
            
            // If we've tried too many times, add a random component as a last resort
            if ($attempt >= $maxRetries) {
                $randomSuffix = sprintf('%04d', mt_rand(1000, 9999));
                return $slipNo . '-' . $randomSuffix;
            }
            
            // Wait a tiny bit before retrying to ensure microtime changes
            usleep(100); // Sleep for 100 microseconds

        } while (true);
    }
    
    /**
     * Generates the base slip number by incrementing the counter within a transaction.
     * This ensures the counter increment is atomic.
     */
    private function generateBaseSlipNumberWithCounter(): string
    {
        $currentDate = Carbon::now()->format('Ymd'); // e.g., 20250628
        $currentTime = Carbon::now()->format('His'); // e.g., 143005
        $customString = 'mk-2d';

        // Use a database transaction to ensure atomicity for the counter increment
        return DB::transaction(function () use ($currentDate, $currentTime, $customString) {
            // Get the current counter or create one if it doesn't exist
            // Using `lockForUpdate()` ensures that concurrent requests will wait
            // for the current transaction to finish before reading/updating the counter.
            $counter = SlipNumberCounter::lockForUpdate()->firstOrCreate(
                ['id' => 1], 
                ['current_number' => 0]
            );
            
            // Increment the counter and get the new value
            $newNumber = $counter->increment('current_number');
            $randomNumber = sprintf('%06d', $newNumber);

            return "{$customString}-{$currentDate}-{$currentTime}-{$randomNumber}";
        });
    }
}