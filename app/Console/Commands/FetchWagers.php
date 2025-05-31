<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Bet;
use Carbon\Carbon;

class FetchWagers extends Command
{
    protected $signature = 'wagers:fetch';
    protected $description = 'Fetch wagers from Seamless API and store them in the database';

    public function handle()
    {
        Log::debug('Starting FetchWagers command...');

        $operatorCode = config('seamless.agent_code');
        $secretKey = config('seamless.secret_key');
        $apiUrl = config('seamless.api_url');

        Log::debug('API Config', [
            'operator_code' => $operatorCode,
            'api_url' => $apiUrl,
        ]);

        if (empty($operatorCode) || empty($secretKey) || empty($apiUrl)) {
            Log::error('Seamless API configuration is missing');
            return;
        }

        $start = Carbon::now()->subMinutes(2);
        $end = $start->copy()->addMinutes(5);

        $startTimestamp = $start->timestamp * 1000;
        $endTimestamp = $end->timestamp * 1000;
       // $requestTime = Carbon::now()->timestamp * 1000;
       $requestTime = now()->timestamp;


        $signString = $requestTime . $secretKey . 'getwagers' . $operatorCode;
        $sign = md5($signString);

        Log::debug('Request Parameters', [
            'start' => $startTimestamp,
            'end' => $endTimestamp,
            'request_time' => $requestTime,
            'sign' => $sign
        ]);

        $url = "{$apiUrl}/api/operators/wagers";
        Log::debug("Sending GET request to: {$url}");

        $response = Http::get($url, [
            'operator_code' => $operatorCode,
            'start' => $startTimestamp,
            'end' => $endTimestamp,
            'request_time' => $requestTime,
            'sign' => $sign,
            'size' => 1000
        ]);

        Log::debug('API Response Status', [
            'status' => $response->status(),
            'successful' => $response->successful()
        ]);

        if ($response->successful()) {
            $data = $response->json();
            Log::debug('API Response Data', [
                'data' => $data
            ]);

            if (isset($data['wagers'])) {
                Log::info('Processing wagers...', [
                    'count' => count($data['wagers'])
                ]);

                foreach ($data['wagers'] as $wager) {
                    Log::debug('Processing wager', [
                        'wager_id' => $wager['id'] ?? 'N/A',
                        'member_account' => $wager['member_account'] ?? 'N/A'
                    ]);

                    Bet::updateOrCreate(
                        ['id' => $wager['id']], // update if exists
                        [
                            'member_account' => $wager['member_account'] ?? '',
                            'round_id' => $wager['round_id'] ?? '',
                            'currency' => $wager['currency'] ?? '',
                            'provider_id' => $wager['provider_id'] ?? 0,
                            'provider_line_id' => $wager['provider_line_id'] ?? 0,
                            'game_type' => $wager['game_type'] ?? '',
                            'game_code' => $wager['game_code'] ?? '',
                            'valid_bet_amount' => $wager['valid_bet_amount'] ?? 0,
                            'bet_amount' => $wager['bet_amount'] ?? 0,
                            'prize_amount' => $wager['prize_amount'] ?? 0,
                            'status' => $wager['status'] ?? '',
                            'settled_at' => isset($wager['settled_at']) ? Carbon::createFromTimestampMs($wager['settled_at']) : null,
                            'created_at' => isset($wager['created_at']) ? Carbon::createFromTimestampMs($wager['created_at']) : now(),
                            'updated_at' => isset($wager['updated_at']) ? Carbon::createFromTimestampMs($wager['updated_at']) : now(),
                        ]
                    );
                }

                $this->info('Wagers fetched and stored successfully.');
            } else {
                Log::warning('No wagers found in the response.');
                $this->warn('No wagers found in the response.');
            }
        } else {
            Log::error('Failed to fetch wagers', ['response' => $response->body()]);
            $this->error('Failed to fetch wagers.');
        }

        Log::debug('FetchWagers command finished.');
    }
}
