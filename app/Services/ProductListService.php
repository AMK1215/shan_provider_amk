<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ProductListService
{
    public static function getProductList(int $offset = 0, ?int $size = null)
    {
        $operator_code = config('seamless_key.agent_code');
        $secret_key = config('seamless_key.secret_key');
        $api_url = rtrim(config('seamless_key.api_url'), '/');
        $request_time = time();
        $sign_str = $request_time . $secret_key . 'product list' . $operator_code;
        $sign = md5($sign_str);

        $params = [
            'operator_code' => $operator_code,
            'sign' => $sign,
            'request_time' => $request_time,
            'offset' => $offset,
        ];
        if ($size !== null) {
            $params['size'] = $size;
        }

        $response = Http::get("{$api_url}/api/operators/available-products", $params);
        return $response->json();
    }
} 