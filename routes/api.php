<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\gplus\Webhook\ProductListController;
use App\Http\Controllers\Api\V1\gplus\Webhook\GameListController;
use App\Http\Controllers\Api\V1\gplus\Webhook\GetBalanceController;
use App\Http\Controllers\Api\V1\gplus\Webhook\WithdrawController;
use App\Http\Controllers\Api\V1\gplus\Webhook\DepositController;
use App\Http\Controllers\Api\V1\gplus\Webhook\PushBetDataController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Game\LaunchGameController;
use App\Http\Controllers\Api\V1\Home\GSCPlusProviderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/player-change-password', [AuthController::class, 'playerChangePassword']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('product-list', [ProductListController::class, 'index']);
Route::get('operators/provider-games', [GameListController::class, 'index']);

Route::prefix('v1/api/seamless')->group(function () {
    Route::post('balance', [GetBalanceController::class, 'getBalance']);
    Route::post('withdraw', [WithdrawController::class, 'withdraw']);
    Route::post('deposit', [DepositController::class, 'deposit']);
    Route::post('pushbetdata', [PushBetDataController::class, 'pushBetData']);
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/seamless/launch-game', [LaunchGameController::class, 'launchGame']);
    // user api
    Route::get('user', [AuthController::class, 'getUser']);
    Route::get('/banks', [GSCPlusProviderController::class, 'banks']);

    // games
    Route::get('/game_types', [GSCPlusProviderController::class, 'gameTypes']);
    Route::get('/providers/{type}', [GSCPlusProviderController::class, 'providers']);
    Route::get('/game_lists/{type}/{provider}', [GSCPlusProviderController::class, 'gameLists']);
    Route::get('/hot_game_lists', [GSCPlusProviderController::class, 'hotGameLists']);

});