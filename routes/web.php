<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TelegramBotController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

require_once __DIR__.'/admin.php';

Auth::routes();
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/profile', [HomeController::class, 'profile'])->name('profile');

//auth routes
Route::get('/login', [LoginController::class, 'showLogin'])->name('showLogin');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');
Route::get('get-change-password', [LoginController::class, 'changePassword'])->name('getChangePassword');
Route::post('update-password/{user}', [LoginController::class, 'updatePassword'])->name('updatePassword');


// telegram routes
Route::get('/telegram-home', [App\Http\Controllers\TelegramBotController::class, 'index']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendMessage', [App\Http\Controllers\TelegramBotController::class, 'sendMessage']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendPhoto', [App\Http\Controllers\TelegramBotController::class, 'sendPhoto']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendAudio', [App\Http\Controllers\TelegramBotController::class, 'sendAudio']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendVideo', [App\Http\Controllers\TelegramBotController::class, 'sendVideo']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendVoice', [App\Http\Controllers\TelegramBotController::class, 'sendVoice']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendDocument', [App\Http\Controllers\TelegramBotController::class, 'sendDocument']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendLocation', [App\Http\Controllers\TelegramBotController::class, 'sendLocation']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendVenue', [App\Http\Controllers\TelegramBotController::class, 'sendVenue']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendContact', [App\Http\Controllers\TelegramBotController::class, 'sendContact']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::get('sendPoll', [App\Http\Controllers\TelegramBotController::class, 'sendPoll']);
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Route::post('telegram-message-webhook', [App\Http\Controllers\TelegramBotController::class, 'telegram_webhook']);

// TeleBot Webhook Route
Route::post('telegram/webhook', [WeStacks\TeleBot\Laravel\Controllers\WebhookController::class, 'handle']);

// Webhook Management Routes
Route::get('telegram/webhook/info', [App\Http\Controllers\TelegramBotController::class, 'getWebhookInfo']);
Route::get('telegram/webhook/set', [App\Http\Controllers\TelegramBotController::class, 'setWebhook']);
Route::get('telegram/webhook/delete', [App\Http\Controllers\TelegramBotController::class, 'deleteWebhook']);

// Test Panel Route
Route::get('telegram/test', [App\Http\Controllers\TelegramBotController::class, 'testPanel']);

Route::post('/web-chat/send', [TelegramChatController::class, 'send'])->name('web.telegram.send');
