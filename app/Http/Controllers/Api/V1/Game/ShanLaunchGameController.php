<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ShanLaunchGameController extends Controller
{
    public function launch(Request $request)
    {
        // 1. Validate input (including sign and operator_code)
        $validator = Validator::make($request->all(), [
            'member_account' => 'required|string|max:50',
            'operator_code'  => 'required|string',
            'sign'           => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $member_account = $request->member_account;
        $operator_code = $request->operator_code;
        $sign = $request->sign;

        // 2. Signature check
        $secret_key = config('shan.services.shan_key'); // or fetch from DB as needed
        $expected_sign = md5($operator_code . $member_account . $secret_key);

        if ($sign !== $expected_sign) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Signature invalid',
            ], 403);
        }

        // 3. Member must exist
        $user = User::where('user_name', $member_account)->first();
        if (!$user) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Member not found',
            ], 404);
        }

        // 4. Call Provider API to get Launch Game URL
        $providerUrl = 'https://ponewine20x.xyz/api/shan/launch-game'; // e.g. 'https://provider-site.com/api/shan/launch-game'
        $response = Http::post($providerUrl, [
            'member_account' => $member_account,
            'operator_code'  => $operator_code,
            'sign'           => $sign,
        ]);
        
        // 5. Pass back provider's response (or parse/modify as needed)
        if ($response->successful()) {
            return response()->json($response->json(), $response->status());
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Provider API error',
                'error_detail' => $response->body()
            ], $response->status());
        }
    }
}







