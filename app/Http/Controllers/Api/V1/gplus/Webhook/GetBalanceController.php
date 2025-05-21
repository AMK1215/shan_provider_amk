<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Enums\SeamlessWalletCode;

class GetBalanceController extends Controller
{
    public function getBalance(Request $request)
    {
        // Validate request
        $request->validate([
            'batch_requests' => 'required|array',
            'operator_code' => 'required|string',
            'currency' => 'required|string',
            'sign' => 'required|string',
            'request_time' => 'required|integer',
        ]);

        // Signature check
        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code .
            $request->request_time .
            'getbalance' .
            $secretKey
        );
        if (strtolower($request->sign) !== strtolower($expectedSign)) {
            return ApiResponseService::error(
                SeamlessWalletCode::InvalidSignature,
                'Invalid signature'
            );
        }

        $results = [];
        foreach ($request->batch_requests as $req) {
            $user = User::where('user_name', $req['member_account'])->first();
            if ($user && $user->wallet) {
                $results[] = [
                    'member_account' => $req['member_account'],
                    'product_code' => $req['product_code'],
                    'balance' => $user->wallet->balanceFloat,
                ];
            } else {
                $results[] = [
                    'member_account' => $req['member_account'],
                    'product_code' => $req['product_code'],
                    'balance' => null,
                    'error' => 'Member not found'
                ];
            }
        }

        return ApiResponseService::success($results);
    }
} 