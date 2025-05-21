<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GameListService;

class GameListController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'product_code' => 'required|integer',
            'operator_code' => 'required|string',
            'offset' => 'integer',
            'size' => 'integer',
            'game_type' => 'string|nullable',
        ]);

        $product_code = $request->input('product_code');
        $operator_code = $request->input('operator_code');
        $game_type = $request->input('game_type');
        $offset = $request->input('offset', 0);
        $size = $request->input('size');

        $result = GameListService::getGameList($product_code, $operator_code, $game_type, $offset, $size);
        return response()->json($result);
    }
} 