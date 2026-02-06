<?php

use App\Http\Controllers\Rpc\MainController;
use App\Http\Controllers\Telegram\RegisterController as TelegramRegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/switch-theme',function (Request $request){
    $user = \App\Models\User::find($request->user_id);
    return $user->switchTheme();
})->name('switchTheme');

Route::post('/telegram/register', [TelegramRegisterController::class, 'register']);
Route::post('/telegram/status', [TelegramRegisterController::class, 'status']);
Route::post('/telegram/set-language', [TelegramRegisterController::class, 'setLanguage']);

Route::post('/rpc', [MainController::class, 'index']);
