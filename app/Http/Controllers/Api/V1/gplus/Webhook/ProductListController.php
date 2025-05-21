<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductListService;

class ProductListController extends Controller
{
    public function index(Request $request)
    {
        $offset = $request->query('offset', 0);
        $size = $request->query('size');
        $result = ProductListService::getProductList((int)$offset, $size !== null ? (int)$size : null);
        return response()->json($result);
    }
}
