<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();
Route::group(['middleware'=>"auth"],function (){
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::resource('users', App\Http\Controllers\Blade\UserController::class);
    Route::resource('roles', App\Http\Controllers\Blade\RolesController::class);
    Route::get('permissions', [App\Http\Controllers\Blade\PermissionsController::class,'index'])->name('permissions.index');
    Route::post('currencies/{currency}/set-default', [App\Http\Controllers\Blade\CurrencyController::class, 'setDefault'])
        ->name('currencies.set-default');
    Route::post('currencies/{currency}/toggle-active', [App\Http\Controllers\Blade\CurrencyController::class, 'toggleActive'])
        ->name('currencies.toggle-active');
    Route::resource('currencies', App\Http\Controllers\Blade\CurrencyController::class)->except('show');
});
