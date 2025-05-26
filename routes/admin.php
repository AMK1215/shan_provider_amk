<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdsVedioController;
use App\Http\Controllers\Admin\WinnerTextController;
use App\Http\Controllers\Admin\TopTenWithdrawController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BannerAdsController;
use App\Http\Controllers\Admin\BannerTextController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Http\Controllers\Admin\BankController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\TransferLog\TransferLogController;
use App\Http\Controllers\Admin\WagerListController;
use App\Http\Controllers\Admin\LocalWagerController;
use App\Http\Controllers\Admin\ReportController;


Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['auth', 'checkBanned'],
], function () {

    Route::post('balance-up', [HomeController::class, 'balanceUp'])->name('balanceUp');
    Route::get('logs/{id}', [HomeController::class, 'logs'])
        ->name('logs');

    // to do
    Route::get('/changePassword/{user}', [HomeController::class, 'changePassword'])->name('changePassword');
    Route::post('/updatePassword/{user}', [HomeController::class, 'updatePassword'])->name('updatePassword');

    Route::get('/changeplayersite/{user}', [HomeController::class, 'changePlayerSite'])->name('changeSiteName');

    Route::post('/updatePlayersite/{user}', [HomeController::class, 'updatePlayerSiteLink'])->name('updateSiteLink');

    Route::get('/player-list', [HomeController::class, 'playerList'])->name('playerList');


    // banner etc start 
    
    Route::resource('video-upload', AdsVedioController::class);
    Route::resource('winner_text', WinnerTextController::class);
    Route::resource('top-10-withdraws', TopTenWithdrawController::class);
    Route::resource('banners', BannerController::class);
    Route::resource('adsbanners', BannerAdsController::class);
    Route::resource('text', BannerTextController::class);
    Route::resource('/promotions', PromotionController::class);
    Route::resource('contact', ContactController::class);
    Route::resource('paymentTypes', PaymentTypeController::class);
    Route::resource('bank', BankController::class);
    // banner etc end

    // master, agent, sub-agent 
    Route::resource('master', MasterController::class);
    Route::get('master-player-list', [MasterController::class, 'MasterPlayerList'])->name('GetMasterPlayerList');
    Route::get('master-cash-in/{id}', [MasterController::class, 'getCashIn'])->name('master.getCashIn');
    Route::post('master-cash-in/{id}', [MasterController::class, 'makeCashIn'])->name('master.makeCashIn');
    Route::get('master/cash-out/{id}', [MasterController::class, 'getCashOut'])->name('master.getCashOut');
    Route::post('master/cash-out/update/{id}', [MasterController::class, 'makeCashOut'])
        ->name('master.makeCashOut');
    Route::put('master/{id}/ban', [MasterController::class, 'banMaster'])->name('master.ban');
    Route::get('master-changepassword/{id}', [MasterController::class, 'getChangePassword'])->name('master.getChangePassword');
    Route::post('master-changepassword/{id}', [MasterController::class, 'makeChangePassword'])->name('master.makeChangePassword');
    Route::get('/master-report/{id}', [MasterController::class, 'MasterReportIndex'])->name('master.report');

    // master, agent sub-agent end
    Route::get('transer-log', [TransferLogController::class, 'index'])->name('transferLog');
    Route::get('transferlog/{id}', [TransferLogController::class, 'transferLog'])->name('transferLogDetail');

    Route::get('wager-list', [WagerListController::class, 'index'])->name('wager-list');
    Route::get('wager-list/fetch', [WagerListController::class, 'fetch'])->name('wager-list.fetch');
    Route::get('wager-list/export-csv', [WagerListController::class, 'exportCsv'])->name('wager-list.export-csv');
    Route::get('wager-list/{id}', [WagerListController::class, 'show'])->name('wager-list.show');
    Route::get('wager-list/{wager_code}/game-history', [WagerListController::class, 'gameHistory'])->name('wager-list.game-history');

    Route::get('local-wager', [LocalWagerController::class, 'index'])->name('local-wager.index');
    Route::get('local-wager/{id}', [LocalWagerController::class, 'show'])->name('local-wager.show');

    Route::get('report', [ReportController::class, 'index'])->name('report.index');

});
