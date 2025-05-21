<?php

namespace App\Services;

use App\Enums\SeamlessWalletCode;

class ApiResponseService
{
    /**
     * Return a standardized API success response using SeamlessWalletCode.
     */
    public static function success(mixed $data = null, string $message = 'Success')
    {
        return [
            'code' => SeamlessWalletCode::Success->value,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Return a standardized API error response using SeamlessWalletCode.
     */
    public static function error(SeamlessWalletCode $code, string $message, mixed $data = null)
    {
        return [
            'code' => $code->value,
            'message' => $message,
            'data' => $data,
        ];
    }
} 