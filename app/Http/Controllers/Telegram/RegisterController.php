<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tg_user_id' => $this->telegramIdRules(),
            'phone' => ['required', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error('invalid_request', $validator->errors()->first());
        }

        $data = $validator->validated();
        $tgId = $data['tg_user_id'];
        $phoneRaw = $data['phone'];
        $name = $data['name'] ?? 'Telegram User';

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

    public function status(Request $request, CurrencyService $currencies)
    {
        $validator = Validator::make($request->all(), [
            'tg_user_id' => $this->telegramIdRules(),
        ]);

        if ($validator->fails()) {
            return $this->error('invalid_request', $validator->errors()->first());
        }

        $tgId = $validator->validated()['tg_user_id'];
        $user = User::where('tg_user_id', $tgId)->first();

        return response()->json([
            'status' => 'ok',
            'registered' => (bool) ($user && $user->phone),
            'language' => $user?->language,
            'currency' => $user
                ? $currencies->serialize($currencies->preferredCurrency($user))
                : $currencies->serialize($currencies->defaultCurrency()),
        ]);
    }

    public function setLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tg_user_id' => $this->telegramIdRules(),
            'language' => ['required', 'string', 'in:ru,uz,en'],
        ]);

        if ($validator->fails()) {
            return $this->error('invalid_request', $validator->errors()->first());
        }

        $data = $validator->validated();
        $tgId = $data['tg_user_id'];
        $language = strtolower($data['language']);

        try {
            $user = DB::transaction(function () use ($tgId, $language) {
                $user = User::where('tg_user_id', $tgId)->lockForUpdate()->first();

                if (!$user) {
                    $user = User::create([
                        'name' => 'Telegram User',
                        'email' => "tg_{$tgId}@local",
                        'password' => Hash::make(Str::random(16)),
                        'tg_user_id' => $tgId,
                        'settings' => [],
                    ]);
                }

                $user->language = $language;
                $user->save();

                return $user->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Telegram set language error', [
                'tg_user_id' => $tgId,
                'language' => $language,
                'error' => $e->getMessage(),
            ]);
            return $this->error('server_error', 'Не удалось сохранить язык', 500);
        }

        return response()->json([
            'status' => 'ok',
            'language' => $user->language,
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

    private function telegramIdRules(): array
    {
        return [
            'required',
            function (string $attribute, mixed $value, \Closure $fail) {
                if (!is_scalar($value)) {
                    $fail('The '.$attribute.' field must be a string.');
                    return;
                }

                $stringValue = (string) $value;

                if ($stringValue === '' || strlen($stringValue) > 64 || !preg_match('/^\d+$/', $stringValue)) {
                    $fail('The '.$attribute.' field format is invalid.');
                }
            },
        ];
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
