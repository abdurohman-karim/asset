<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return ($user->hasPermission($ability) || $user->hasRole('Super Admin')) ? true : null;
        });
    }
}