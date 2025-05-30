<?php

use App\Http\Controllers\Admin\AdsVedioController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\BankController;
use App\Http\Controllers\Admin\BannerAdsController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BannerTextController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\LocalWagerController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubAccountController;
use App\Http\Controllers\Admin\TopTenWithdrawController;
use App\Http\Controllers\Admin\TransferLogController;
use App\Http\Controllers\Admin\WagerListController;
use App\Http\Controllers\Admin\WinnerTextController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DepositRequestController;
use App\Http\Controllers\Admin\WithDrawRequestController;

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
    // deposit request start
    Route::get('finicialwithdraw', [WithDrawRequestController::class, 'index'])->name('agent.withdraw');
    Route::post('finicialwithdraw/{withdraw}', [WithDrawRequestController::class, 'statusChangeIndex'])->name('agent.withdrawStatusUpdate');
    Route::post('finicialwithdraw/reject/{withdraw}', [WithDrawRequestController::class, 'statusChangeReject'])->name('agent.withdrawStatusreject');

    Route::get('finicialdeposit', [DepositRequestController::class, 'index'])->name('agent.deposit');
    Route::get('finicialdeposit/{deposit}', [DepositRequestController::class, 'view'])->name('agent.depositView');
    Route::post('finicialdeposit/{deposit}', [DepositRequestController::class, 'statusChangeIndex'])->name('agent.depositStatusUpdate');
    Route::post('finicialdeposit/reject/{deposit}', [DepositRequestController::class, 'statusChangeReject'])->name('agent.depositStatusreject');

    // deposit request end

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
    Route::put('subacc/{id}/ban', [SubAccountController::class, 'banSubAcc'])->name('subacc.ban');
    Route::get('subacc-changepassword/{id}', [SubAccountController::class, 'getChangePassword'])->name('subacc.getChangePassword');
    Route::post('subacc-changepassword/{id}', [SubAccountController::class, 'makeChangePassword'])->name('subacc.makeChangePassword');
    Route::get('subacc-permission/{id}', [SubAccountController::class, 'permission'])->name('subacc.permission');
    Route::post('subacc-permission/update/{id}', [SubAccountController::class, 'updatePermission'])->name('subacc.permission.update');
    Route::get('subacc-profile/{id}', [SubAccountController::class, 'subAgentProfile'])
        ->name('subacc.profile');
    Route::get('subacc-agent-players', [SubAccountController::class, 'agentPlayers'])
        ->name('subacc.agent_players');
    Route::get('subacc/player/{id}/report', [SubAccountController::class, 'playerReport'])->name('subacc.player.report_detail');
    Route::get('subacc/player-cash-in/{player}', [SubAccountController::class, 'getCashIn'])->name('subacc.player.getCashIn');
    Route::post('subacc/player-cash-in/{player}', [SubAccountController::class, 'makeCashIn'])->name('subacc.player.makeCashIn');
    Route::get('/subacc/player/cash-out/{player}', [SubAccountController::class, 'getCashOut'])->name('subacc.player.getCashOut');
    Route::post('/subacc/player/cash-out/update/{player}', [SubAccountController::class, 'makeCashOut'])
        ->name('subacc.player.makeCashOut');
    Route::get('/subagentacc/tran-logs', [SubAccountController::class, 'SubAgentTransferLog'])->name('subacc.tran.logs');
    // sub-agent end
    // agent create player start
    Route::resource('player', PlayerController::class);
    Route::put('player/{id}/ban', [PlayerController::class, 'banUser'])->name('player.ban');
    Route::get('player-cash-in/{player}', [PlayerController::class, 'getCashIn'])->name('player.getCashIn');
    Route::post('player-cash-in/{player}', [PlayerController::class, 'makeCashIn'])->name('player.makeCashIn');
    Route::get('player/cash-out/{player}', [PlayerController::class, 'getCashOut'])->name('player.getCashOut');
    Route::post('player/cash-out/update/{player}', [PlayerController::class, 'makeCashOut'])
        ->name('player.makeCashOut');
    Route::get('player-changepassword/{id}', [PlayerController::class, 'getChangePassword'])->name('player.getChangePassword');
    Route::post('player-changepassword/{id}', [PlayerController::class, 'makeChangePassword'])->name('player.makeChangePassword');
    Route::get('/players-list', [PlayerController::class, 'player_with_agent'])->name('playerListForAdmin');
    // agent create player end
    // report log

    Route::get('/agent-report/{id}', [AgentController::class, 'agentReportIndex'])->name('agent.report');
    Route::get('/player-report/{id}', [PlayerController::class, 'playerReportIndex'])->name('player.report');

    // Shan Report
    Route::get('/shan-report', [ReportController::class, 'shanReportIndex'])->name('shan_report');

    // master, agent sub-agent end
    Route::get('/transfer-logs', [TransferLogController::class, 'index'])->name('transfer-logs.index');

    // Route::get('transer-log', [TransferLogController::class, 'index'])->name('transferLog');
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
    // Route::get('report/detail/{member_account}', [\App\Http\Controllers\Admin\ReportController::class, 'getReportDetails'])->name('admin.report.detail');

});
