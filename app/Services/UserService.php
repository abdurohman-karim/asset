<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function register(array $params): array
    {
        $tgId = Arr::get($params, 'tg_user_id');

        if (!$tgId) {
            throw new \RuntimeException('tg_user_id required');
        }

        $name = Arr::get($params, 'name', 'Telegram User');
        $email = "tg_{$tgId}@local";
        $password = Hash::make(Str::random(16));

        $user = User::updateOrCreate(
            ['tg_user_id' => $tgId],
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'settings' => [],
            ]
        );

        return [
            'id' => $user->id,
            'tg_user_id' => $user->tg_user_id,
            'name' => $user->name,
        ];
    }

    public function resolveUser(array $params, $authUser = null)
    {
        if ($authUser) {
            return $authUser;
        }

        if ($tgId = Arr::get($params, 'tg_user_id')) {
            return User::where('tg_user_id', $tgId)->first();
        }

        if ($userId = Arr::get($params, 'user_id')) {
            return User::find($userId);
        }

        return null;
    }
}
