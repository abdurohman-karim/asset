<?php

use App\Http\Controllers\Rpc\MainController;
use App\Http\Controllers\Telegram\RegisterController as TelegramRegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('telegram.secret')->group(function () {
    Route::post('/telegram/register', [TelegramRegisterController::class, 'register']);
    Route::post('/telegram/status', [TelegramRegisterController::class, 'status']);
    Route::post('/telegram/set-language', [TelegramRegisterController::class, 'setLanguage']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/switch-theme', function (Request $request) {
        return $request->user()->switchTheme();
    })->name('switchTheme');
});

Route::middleware('rpc.auth')->group(function () {
    Route::post('/rpc', [MainController::class, 'index']);
});
