<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $tgId = $request->input('tg_user_id');
        $phoneRaw = $request->input('phone');
        $name = $request->input('name', 'Telegram User');

        if (!$tgId || !$phoneRaw) {
            return $this->error('invalid_request', 'Неверные данные запроса');
        }

        $phone = $this->normalizePhone($phoneRaw);
        if (!$phone) {
            return $this->error('invalid_phone', 'Неверный формат номера телефона');
        }

        $phoneOwner = User::where('phone', $phone)
            ->where('tg_user_id', '!=', $tgId)
            ->first();

        if ($phoneOwner) {
            return $this->error('phone_in_use', 'Номер уже используется другим аккаунтом');
        }

        try {
            $user = DB::transaction(function () use ($tgId, $phone, $name) {
                $user = User::where('tg_user_id', $tgId)->lockForUpdate()->first();

                if ($user) {
                    $user->name = $name ?: $user->name;
                    $user->phone = $phone;
                    $user->save();
                    return $user->fresh();
                }

                return User::create([
                    'name' => $name ?: 'Telegram User',
                    'email' => "tg_{$tgId}@local",
                    'password' => Hash::make(Str::random(16)),
                    'phone' => $phone,
                    'tg_user_id' => $tgId,
                    'settings' => [],
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Telegram register error', [
                'tg_user_id' => $tgId,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return $this->error('server_error', 'Не удалось завершить регистрацию', 500);
        }

        return response()->json([
            'status' => 'ok',
            'user_id' => $user->id,
            'phone' => $user->phone,
        ]);
    }

    public function status(Request $request)
    {
        $tgId = $request->input('tg_user_id');
        if (!$tgId) {
            return $this->error('invalid_request', 'Неверные данные запроса');
        }

        $user = User::where('tg_user_id', $tgId)->first();

        return response()->json([
            'status' => 'ok',
            'registered' => (bool) ($user && $user->phone),
        ]);
    }

    private function normalizePhone(string $phone): ?string
    {
        $raw = preg_replace('/[^\d+]/', '', $phone);
        if (!$raw) {
            return null;
        }

        if (str_starts_with($raw, '00')) {
            $raw = '+' . substr($raw, 2);
        }

        if (!str_starts_with($raw, '+')) {
            $raw = '+' . $raw;
        }

        $digits = preg_replace('/\D/', '', $raw);
        $length = strlen($digits);

        if ($length < 8 || $length > 15) {
            return null;
        }

        return '+' . $digits;
    }

    private function error(string $code, string $message, int $status = 422)
    {
        return response()->json([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
        ], $status);
    }
}
