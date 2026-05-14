<?php

use Illuminate\Support\Facades\App as AppFacade;
use Illuminate\Support\Facades\Route;

$supportedWelcomeLocales = ['ru', 'en', 'uz'];

Route::get('/', function () use ($supportedWelcomeLocales) {
    $locale = session('welcome_locale', request()->cookie('welcome_locale', 'ru'));

    if (! in_array($locale, $supportedWelcomeLocales, true)) {
        $locale = 'ru';
    }

    AppFacade::setLocale($locale);
    AppFacade::setFallbackLocale('ru');
    session(['welcome_locale' => $locale]);

    return view('welcome', [
        'welcomeLocale' => $locale,
        'welcomeLocales' => $supportedWelcomeLocales,
    ]);
})->name('welcome');

Route::get('/lang/{locale}', function (string $locale) use ($supportedWelcomeLocales) {
    if (! in_array($locale, $supportedWelcomeLocales, true)) {
        $locale = 'ru';
    }

    session(['welcome_locale' => $locale]);

    return redirect()
        ->route('welcome')
        ->cookie('welcome_locale', $locale, 60 * 24 * 365);
})->name('welcome.language');

Auth::routes();
Route::group(['middleware'=>"auth"],function (){
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::prefix('artisan-console')->name('artisan-console.')->middleware('throttle:20,1')->group(function () {
        Route::get('/', [App\Http\Controllers\Blade\ArtisanConsoleController::class, 'index'])->name('index');
        Route::post('/run', [App\Http\Controllers\Blade\ArtisanConsoleController::class, 'run'])->name('run')->middleware('throttle:10,1');
    });

    Route::resource('users', App\Http\Controllers\Blade\UserController::class);
    Route::resource('roles', App\Http\Controllers\Blade\RolesController::class);
    Route::get('permissions', [App\Http\Controllers\Blade\PermissionsController::class,'index'])->name('permissions.index');
    Route::post('currencies/{currency}/set-default', [App\Http\Controllers\Blade\CurrencyController::class, 'setDefault'])
        ->name('currencies.set-default');
    Route::post('currencies/{currency}/toggle-active', [App\Http\Controllers\Blade\CurrencyController::class, 'toggleActive'])
        ->name('currencies.toggle-active');
    Route::resource('currencies', App\Http\Controllers\Blade\CurrencyController::class)->except('show');
});
