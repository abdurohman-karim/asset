<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/switch-theme',function (Request $request){
    $user = \App\Models\User::find($request->user_id);
    return $user->switchTheme();
})->name('switchTheme');
