<?php

namespace App\Services;

use App\Helpers\SessionHelper;
use App\Models\TwoDigit\Bettle;
use App\Models\TwoDigit\ChooseDigit;
use App\Models\TwoDigit\HeadClose;
use App\Models\TwoDigit\SlipNumberCounter;
use App\Models\TwoDigit\TwoBet;
use App\Models\TwoDigit\TwoDLimit;
use App\Models\User;
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

        $sessionType = SessionHelper::getCurrentSession();
        $gameDate = Carbon::now()->format('Y-m-d');
        $gameTime = $currentBettle->end_time;

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
                return $overLimitDigits;
            }

            // Generate a unique slip number ONCE for this entire batch of bets
            // It's crucial that this slip number is truly unique before we proceed
            $slipNo = $this->generateUniqueSlipNumber();
            Log::info("Generated Slip No for batch: {$slipNo}");


            $beforeBalance = $user->main_balance;

            // Deduct total amount from user's main_balance
            $user->main_balance -= $totalBetAmount;
            $user->save();

            $afterBalance = $user->main_balance;

            foreach ($amounts as $betDetail) {
                $twoDigit = str_pad($betDetail['num'], 2, '0', STR_PAD_LEFT);
                $subAmount = $betDetail['amount'];

                $chooseDigit = ChooseDigit::where('choose_close_digit', $twoDigit)->first();
                $headClose = HeadClose::where('head_close_digit', substr($twoDigit, 0, 1))->first();

                TwoBet::create([
                    'user_id' => $user->id,
                    'member_name' => $user->user_name,
                    'bettle_id' => $currentBettle->id,
                    'choose_digit_id' => $chooseDigit ? $chooseDigit->id : null,
                    'head_close_id' => $headClose ? $headClose->id : null,
                    'agent_id' => $user->agent_id,
                    'bet_number' => $twoDigit,
                    'bet_amount' => $subAmount,
                    'total_bet_amount' => $totalBetAmount,
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
     * (No changes needed here based on the slip_no issue)
     */
    protected function checkAllLimits(
        array $amounts,
        string $sessionType,
        string $gameDate,
        float $overallBreakAmount,
        ?float $userPersonalLimit
    ): array {
        $overLimitDigits = [];

        $closedTwoDigits = ChooseDigit::where('status', false)
            ->pluck('choose_close_digit')
            ->map(fn ($digit) => str_pad($digit, 2, '0', STR_PAD_LEFT))
            ->unique()
            ->all();

        $closedHeadDigits = HeadClose::where('status', false)
            ->pluck('head_close_digit')
            ->map(fn ($digit) => (string) $digit)
            ->unique()
            ->all();

        foreach ($amounts as $amount) {
            $twoDigit = str_pad($amount['num'], 2, '0', STR_PAD_LEFT);
            $subAmount = $amount['amount'];
            $headDigitOfSelected = substr($twoDigit, 0, 1);

            if (in_array($headDigitOfSelected, $closedHeadDigits)) {
                $overLimitDigits[] = $twoDigit;
                continue;
            }

            if (in_array($twoDigit, $closedTwoDigits)) {
                $overLimitDigits[] = $twoDigit;
                continue;
            }

            $totalBetAmountForTwoDigit = DB::table('two_bets')
                ->where('game_date', $gameDate)
                ->where('session', $sessionType)
                ->where('bet_number', $twoDigit)
                ->sum('bet_amount');

            $projectedTotalBetAmount = $totalBetAmountForTwoDigit + $subAmount;

            if ($projectedTotalBetAmount > $overallBreakAmount) {
                $overLimitDigits[] = $twoDigit;
                continue;
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
     * Generates a truly unique slip number by combining counter, microtime, and a random string.
     */
    protected function generateUniqueSlipNumber(): string
    {
        $maxRetries = 20; // Increased max retries slightly
        $attempt = 0;
        
        do {
            $attempt++;
            
            // Get base slip number which includes date, time, and atomic counter.
            // This is the most critical part for sequential uniqueness.
            $baseSlipNoWithCounter = $this->generateBaseSlipNumberWithCounter();
            
            // Append microtime for high granularity.
            // Ensure microseconds are always 6 digits for consistent length.
            $microtime = microtime(true);
            $microseconds = sprintf('%06d', ($microtime - floor($microtime)) * 1000000);
            
            // Combine with a short, truly random string to virtually eliminate collisions.
            // Using a strong random generator. bin2hex(random_bytes(2)) gives 4 hex chars.
            $randomComponent = bin2hex(random_bytes(2)); // e.g., 'a1b3'
            
            $slipNo = "{$baseSlipNoWithCounter}-{$microseconds}-{$randomComponent}";
            
            // Check for existence in the database.
            // This check is outside the counter's transaction but within the main play() transaction (before commit).
            // It ensures uniqueness against *already committed* records.
            // In case of very high concurrency and a rare collision *before* the commit, this will catch it.
            $exists = DB::table('two_bets')->where('slip_no', $slipNo)->exists();
            
            if (!$exists) {
                return $slipNo;
            }
            
            // Log if a collision was detected, so you can monitor frequency.
            Log::warning("Slip number collision detected (attempt {$attempt}): {$slipNo}");

            // If collision, wait briefly and retry.
            // Sleeping helps ensure microtime changes and gives other transactions a chance to commit.
            usleep(rand(100, 500)); // Sleep for 100-500 microseconds

            // After several attempts, if still colliding, increase random component length or throw.
            if ($attempt >= $maxRetries) {
                // This scenario should be extremely rare with the current generation method.
                // If it happens, it indicates a severe concurrency bottleneck or system clock issue.
                // You might consider throwing an exception here instead of forcing a retry.
                Log::critical("Failed to generate unique slip number after {$maxRetries} attempts. Last attempt: {$slipNo}");
                throw new \Exception("Could not generate a unique slip number. Please try again.");
            }
            
        } while (true);
    }
    
    /**
     * Generates the base slip number by incrementing the counter within a transaction.
     * This ensures the counter increment is atomic and isolated.
     */
    private function generateBaseSlipNumberWithCounter(): string
    {
        $currentDate = Carbon::now()->format('Ymd');
        $currentTime = Carbon::now()->format('His'); // Time in seconds

        $customString = 'mk-2d';

        // Use a database transaction and lock to ensure atomicity for the counter increment
        return DB::transaction(function () use ($currentDate, $currentTime, $customString) {
            // lockForUpdate() acquires a shared lock for "update", which prevents other transactions
            // from acquiring an exclusive lock on the same row until this transaction commits.
            // For a single counter row, this effectively serializes access to it.
            $counter = SlipNumberCounter::lockForUpdate()->firstOrCreate(
                ['id' => 1], 
                ['current_number' => 0]
            );
            
            // Increment the counter and get the new value.
            // This happens atomically within the locked transaction.
            $newNumber = $counter->increment('current_number');
            $paddedCounter = sprintf('%06d', $newNumber);

            return "{$customString}-{$currentDate}-{$currentTime}-{$paddedCounter}";
        });
    }
}