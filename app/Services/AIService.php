<?php

namespace App\Services;

use App\Models\AIInsight;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    public function daily(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User not found (ai.insight.daily)');
        }

        $today = now();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$today->startOfDay(), $today->endOfDay()])
            ->get();

        $goal = Goal::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        // Генерируем промпт
        $prompt = $this->buildDailyPrompt($user, $goal, $transactions);

        // вызываем GPT
        $ai = $this->callLLM($prompt);

        // insight = summary (основная мысль)
        $insight = $ai['summary'] ?? 'Сегодня всё спокойно.';

        return [
            'insight'  => $insight,
            'metadata' => $ai,
        ];
    }

    public function goalAnalysis(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $goalId = Arr::get($params, 'goal_id');
        $goal = Goal::where('user_id', $user->id)->whereKey($goalId)->firstOrFail();

        $prompt = "Проанализируй цель накопления:\n" . json_encode([
                'title' => $goal->title,
                'amount_total' => (float) $goal->amount_total,
                'amount_saved' => (float) $goal->amount_saved,
                'deadline' => optional($goal->deadline)->toDateString(),
                'progress' => $goal->progress,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $aiResponse = $this->callLLM($prompt);

        return $aiResponse;
    }

    public function transactionAnalysis(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $days = (int) Arr::get($params, 'days', 30);
        $from = Carbon::today()->subDays($days);
        $to = Carbon::today();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$from, $to])
            ->get()
            ->map(fn ($t) => [
                'amount' => (float) $t->amount,
                'category' => $t->category,
                'datetime' => $t->datetime?->toDateTimeString(),
            ])->values()->all();

        $prompt = "Проанализируй расходы и доходы пользователя за период {$from->toDateString()} - {$to->toDateString()} и дай советы по экономии. Данные:\n"
            . json_encode($transactions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $aiResponse = $this->callLLM($prompt);

        return $aiResponse;
    }

    protected function buildDailyPrompt(User $user, ?Goal $goal, $transactions): string
    {
        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'goal' => $goal ? [
                'title' => $goal->title,
                'amount_total' => (float) $goal->amount_total,
                'amount_saved' => (float) $goal->amount_saved,
                'progress' => $goal->progress,
                'deadline' => optional($goal->deadline)->toDateString(),
            ] : null,
            'today_transactions' => $transactions->map(fn ($t) => [
                'amount' => (float) $t->amount,
                'category' => $t->category,
                'datetime' => $t->datetime?->toDateTimeString(),
            ])->values()->all(),
        ];

        return "Ты — финансовый коуч. Дай короткий, мотивирующий совет пользователю по поводу его сегодняшних трат и прогресса по цели.\n" .
            "Ответ верни строго в JSON с ключами summary и recommendation и numbers.\n\n" .
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function callLLM(string $prompt): array
    {
        // 1️⃣ Пытаемся вызвать OpenAI
        $result = $this->callOpenAI($prompt);
        if (!$this->isAiError($result)) {
            return $result;
        }

        // 2️⃣ Переключаемся на Groq
        $result = $this->callGroq($prompt);
        if (!$this->isAiError($result)) {
            return $result;
        }

        // 3️⃣ Переключаемся на DeepSeek
        $result = $this->callDeepSeek($prompt);
        if (!$this->isAiError($result)) {
            return $result;
        }

        // 4️⃣ Fallback — ни один провайдер не работает
        return [
            'summary' => "Сегодня совет недоступен — превышен лимит AI.",
            'recommendation' => "Не переживай, продолжай придерживаться бюджета — завтра дам новый совет 💙",
            'numbers' => ['score' => 0.0],
            'provider' => 'fallback'
        ];
    }

    protected function isAiError(array $result): bool
    {
        return isset($result['error']);
    }

    protected function callOpenAI(string $prompt): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4.1-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Ты финансовый ассистент. Отвечай строго в JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
            ]);

            Log::info("OpenAi response: {$response}");

            if ($response->failed()) {
                return [
                    'error' => 'openai_failed',
                    'details' => $response->json()
                ];
            }

            $content = $response->json('choices.0.message.content');
            return json_decode($content, true);

        } catch (\Throwable $e) {
            return ['error' => 'openai_exception', 'details' => $e->getMessage()];
        }
    }

    protected function callGroq(string $prompt): array
    {
        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . config('services.groq.key'),
                "Content-Type"  => "application/json"
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' =>
                            "Ты финансовый ассистент.
                        Всегда отвечай ТОЛЬКО одним JSON без каких-либо комментариев,
                        reasoning, текста перед или после JSON.
                        Строго на русском языке.
                        
                        Формат:
                        {
                          \"summary\": \"...\",
                          \"recommendation\": \"...\",
                          \"numbers\": {
                            \"progress_percent\": 0,
                            \"daily_need\": 0,
                            \"monthly_need\": 0,
                            \"days_left\": 0
                          }
                        }"
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
            ]);

            Log::info("Groq RAW: " . $response->body());

            if ($response->failed()) {
                return ['error' => 'groq_failed', 'status' => $response->status()];
            }

            // Берём контент
            $content = $response->json('choices.0.message.content', null);

            if (!$content || !is_string($content)) {
                return ['error' => 'empty_content'];
            }

            // ---- Декодируем напрямую ----
            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // ---- Если JSON грязный, вырезаем ----
            $start = strpos($content, '{');
            $end   = strrpos($content, '}');

            if ($start !== false && $end !== false) {
                $cleanJson = substr($content, $start, $end - $start + 1);
                $decoded = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            // ---- Фатальная ошибка ----
            return [
                'error' => 'json_decode_error',
                'raw'   => $content
            ];

        } catch (\Throwable $e) {
            Log::error("Groq exception: {$e->getMessage()}");
            return ['error' => 'groq_exception', 'message' => $e->getMessage()];
        }
    }

    protected function callDeepSeek(string $prompt): array
    {
        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . config('services.deepseek.key')
            ])->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ты финансовый ассистент. Отвечай строго в JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
            Log::info("Deepseek response: {$response}");

            if ($response->failed()) {
                return ['error' => 'deepseek_failed'];
            }

            $content = $response->json('choices.0.message.content');
            return json_decode($content, true);

        } catch (\Throwable $e) {
            return ['error' => 'deepseek_exception'];
        }
    }
}
