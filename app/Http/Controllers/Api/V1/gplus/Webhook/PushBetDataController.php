<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PlaceBet;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Enums\SeamlessWalletCode;
use App\Models\User;

class PushBetDataController extends Controller
{
    public function pushBetData(Request $request)
    {
        Log::info('Push Bet Data API Request', ['request' => $request->all()]);

        $request->validate([
            'operator_code' => 'required|string',
            'wagers' => 'required|array',
            'sign' => 'required|string',
            'request_time' => 'required|integer',
        ]);

        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'pushbetdata' .
            $secretKey
        );
        if (strtolower($request->sign) !== strtolower($expectedSign)) {
            Log::warning('Push Bet Data Invalid Signature', ['provided' => $request->sign, 'expected' => $expectedSign]);
            return response()->json([
                'code' => SeamlessWalletCode::InvalidSignature->value,
                'message' => 'Invalid signature',
            ]);
        }

        foreach ($request->wagers as $tx) {
            $memberAccount = $tx['member_account'] ?? null;
            $user = User::where('user_name', $memberAccount)->first();
            if (!$user) {
                Log::warning('Member not found', ['member_account' => $memberAccount]);
                return response()->json([
                    'code' => SeamlessWalletCode::MemberNotExist->value,
                    'message' => 'Member not found',
                ]);
            }
            $transactionId = $tx['wager_code'] ?? null;
            if (!$transactionId) {
                Log::warning('Transaction missing wager_code', ['tx' => $tx]);
                continue;
            }
            $placeBet = PlaceBet::where('transaction_id', $transactionId)->first();
            if ($placeBet) {
                // Update existing
                $placeBet->update([
                    'member_account' => $tx['member_account'] ?? $placeBet->member_account,
                    'product_code' => $tx['product_code'] ?? $placeBet->product_code,
                    'amount' => $tx['bet_amount'] ?? $placeBet->amount,
                    'action' => $tx['wager_type'] ?? $placeBet->action,
                    'status' => $tx['wager_status'] ?? $placeBet->status,
                    'meta' => $tx,
                    'wager_status' => $tx['wager_status'] ?? $placeBet->wager_status,
                    'round_id' => $tx['round_id'] ?? $placeBet->round_id,
                    'game_type' => $tx['game_type'] ?? $placeBet->game_type,
                    'channel_code' => $tx['channel_code'] ?? $placeBet->channel_code,
                    'settled_at' => $tx['settled_at'] ?? $placeBet->settled_at,
                    'created_at_provider' => $tx['created_at'] ?? $placeBet->created_at_provider,
                    'currency' => $tx['currency'] ?? $placeBet->currency,
                    'game_code' => $tx['game_code'] ?? $placeBet->game_code,
                ]);
                Log::info('Updated place_bets record', ['transaction_id' => $transactionId]);
            } else {
                // Insert new
                PlaceBet::create([
                    'transaction_id' => $transactionId,
                    'member_account' => $tx['member_account'] ?? '',
                    'product_code' => $tx['product_code'] ?? 0,
                    'amount' => $tx['bet_amount'] ?? 0,
                    'action' => $tx['wager_type'] ?? '',
                    'status' => $tx['wager_status'] ?? '',
                    'meta' => $tx,
                    'wager_status' => $tx['wager_status'] ?? '',
                    'round_id' => $tx['round_id'] ?? '',
                    'game_type' => $tx['game_type'] ?? '',
                    'channel_code' => $tx['channel_code'] ?? '',
                    'settled_at' => $tx['settled_at'] ?? null,
                    'created_at_provider' => $tx['created_at'] ?? null,
                    'currency' => $tx['currency'] ?? '',
                    'game_code' => $tx['game_code'] ?? '',
                ]);
                Log::info('Inserted new place_bets record', ['transaction_id' => $transactionId]);
            }
        }

        return response()->json([
            'code' => SeamlessWalletCode::Success->value,
            'message' => '',
        ]);
    }
} 