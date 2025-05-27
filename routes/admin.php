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
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\SubAccountController;
use App\Http\Controllers\Admin\PlayerController;


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
    

    // agent start
    Route::resource('agent', AgentController::class);
    Route::get('agent-player-report/{id}', [AgentController::class, 'getPlayerReports'])->name('agent.getPlayerReports');
    Route::get('agent-cash-in/{id}', [AgentController::class, 'getCashIn'])->name('agent.getCashIn');
    Route::post('agent-cash-in/{id}', [AgentController::class, 'makeCashIn'])->name('agent.makeCashIn');
    Route::get('agent/cash-out/{id}', [AgentController::class, 'getCashOut'])->name('agent.getCashOut');
    Route::post('agent/cash-out/update/{id}', [AgentController::class, 'makeCashOut'])
        ->name('agent.makeCashOut');
    Route::put('agent/{id}/ban', [AgentController::class, 'banAgent'])->name('agent.ban');
    Route::get('agent-changepassword/{id}', [AgentController::class, 'getChangePassword'])->name('agent.getChangePassword');
    Route::post('agent-changepassword/{id}', [AgentController::class, 'makeChangePassword'])->name('agent.makeChangePassword');
    // agent end

    // sub-agent start 
    Route::resource('subacc', SubAccountController::class);
    // sub-agent end
    // report log 
    Route::get('/master-report/{id}', [MasterController::class, 'MasterReportIndex'])->name('master.report');
    Route::get('/agent-report/{id}', [AgentController::class, 'agentReportIndex'])->name('agent.report');
    Route::get('/player-report/{id}', [PlayerController::class, 'playerReportIndex'])->name('player.report');

    //Shan Report
    Route::get('/shan-report', [ReportController::class, 'shanReportIndex'])->name('shan_report');


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
    Route::get('report/{member_account}', [ReportController::class, 'show'])->name('report.detail');
    //Route::get('report/detail/{member_account}', [\App\Http\Controllers\Admin\ReportController::class, 'getReportDetails'])->name('admin.report.detail');

});
