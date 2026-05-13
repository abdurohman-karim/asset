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

        return null;
    }

    public function resolveTelegramUser(array $params): ?User
    {
        $tgId = Arr::get($params, 'tg_user_id');

        if (!is_string($tgId) && !is_int($tgId)) {
            return null;
        }

        $tgId = (string) $tgId;

        if (!preg_match('/^\d+$/', $tgId)) {
            return null;
        }

        return User::where('tg_user_id', $tgId)->first();
    }
}
