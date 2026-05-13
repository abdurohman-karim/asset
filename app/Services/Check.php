<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class Check
{
    static function forbiddenAccess():void
    {
        abort(403);
    }

    static function permission($permission):void
    {
        if (!auth()->user()->hasPermission($permission))
            self::forbiddenAccess();
    }

    static function isAdmin():bool
    {
        return Session::has('is_admin') && Session::get('is_admin') == 1;
    }

    static function hasRole($user_id, $role)
    {
        $user = \App\Models\User::where('id', $user_id)->first();
        return $user->roles()->where('name', $role)->exists();
    }
}
