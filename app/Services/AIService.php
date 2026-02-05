<?php

namespace App\Services;

use App\Models\AIInsight;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    public function daily(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User not found (ai.insight.daily)');
        }

        $today = Carbon::today();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereDate('datetime', $today)
            ->get();

        $goal = Goal::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        $prompt = $this->buildDailyPrompt($user, $goal, $transactions);

        $ai = $this->callLLM($prompt);

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

        $cacheKey = "goal_analysis_{$user->id}_{$goal->id}_" . Carbon::today()->toDateString();

        if (Cache::has($cacheKey)) {
            return [
                'summary' => "Сегодня анализ цели недоступен — дневной лимит исчерпан по цели {$goal->title}.",
                'recommendation' => "Не переживай 😊 продолжай идти к своей цели — завтра я подготовлю новый, свежий совет!",
                'numbers' => [
                    'score' => 0.0,
                ],
                'provider' => 'fallback'
            ];
        }

        Cache::put($cacheKey, true, now()->endOfDay());

        $prompt = "Проанализируй цель накопления:\n" . json_encode([
                'title' => $goal->title,
                'amount_total' => (float) $goal->amount_total,
                'amount_saved' => (float) $goal->amount_saved,
                'deadline' => optional($goal->deadline)->toDateString(),
                'progress' => $goal->progress,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $this->callLLM($prompt);
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

        return
            "Ты — персональный финансовый коуч из Узбекистана. 
            Твоя обязанность — давать человеку умный, короткий и практичный совет по финансам.
            
            Важно:
            — ВСЕ суммы строго в валюте UZS.
            — Никогда не используй ₽, рубли, доллары или другие валюты.
            — Ты обязан дать реальный совет, основанный на данных, а не просто пересказывать цифры.
            — Совет должен быть человеческий, мотивирующий и конкретный: что улучшить, что изменить, как вести себя завтра.
            — Summary = короткий вывод о дне (1–2 предложения).
            — Recommendation = конкретный совет (1 предложение).
            — Не повторяй в recommendation то, что уже есть в summary.
            — Не придумывай новых транзакций и сумм.
            
            Формат ответа:
            Верни строго JSON:
            {
              \"summary\": \"краткое человеческое описание дня\",
              \"recommendation\": \"конкретный совет пользователю\",
              \"numbers\": {
                  \"progress_percent\": float,
                  \"daily_total\": int,
                  \"daily_need\": int|null,
                  \"days_left\": int|null
              }
            }
            
            Проанализируй данные ниже и ОБЯЗАТЕЛЬНО дай совет:
          
            "
            . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function getActiveModel(): ?string
    {
        $models = config('services.models_llm');

        foreach ($models as $model) {
            $name = $model['name'];

            $used  = Cache::get("llm_used_{$name}", 0);
            $fail  = Cache::get("llm_fail_{$name}", 0);
            $limit = $model['limit'];

            if ($fail >= 3) {
                continue;
            }

            if ($used >= $limit) {
                continue;
            }

            return $name;
        }

        return null;
    }


    protected function callLLM(string $prompt): array
    {
        while ($model = $this->getActiveModel()) {

            $method = "call" . ucfirst($model);

            try {
                $result = $this->$method($prompt);

                if (!$this->isAiError($result)) {
                    Cache::increment("llm_used_{$model}");
                    return $result;
                }

                Cache::increment("llm_fail_{$model}");

            } catch (\Throwable $e) {
                Cache::increment("llm_fail_{$model}");
            }
        }

        return [
            'summary' => "Сегодня совет недоступен — превышен лимит AI.",
            'recommendation' => "Не волнуйся 😊 завтра я смогу дать новый совет!",
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
                    [
                        'role' => 'system',
                        'content' =>
                            "Ты финансовый ассистент.
                        Всегда отвечай ТОЛЬКО одним JSON без каких-либо комментариев,
                        reasoning, текста перед или после JSON.
                        Строго на русском языке.
                        ВСЕ суммы строго в валюте UZS.
                        
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

            Log::info("OpenAi response: {$response}");

            if ($response->failed()) {
                return [
                    'error' => 'openai_failed',
                    'details' => $response->json()
                ];
            }

            $content = $response->json('choices.0.message.content');
            return $this->decodeAiJson($content, 'openai');

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

            $content = $response->json('choices.0.message.content', null);

            if (!$content || !is_string($content)) {
                return ['error' => 'empty_content'];
            }

            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            $start = strpos($content, '{');
            $end   = strrpos($content, '}');

            if ($start !== false && $end !== false) {
                $cleanJson = substr($content, $start, $end - $start + 1);
                $decoded = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

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
            ]);
            Log::info("Deepseek response: {$response}");

            if ($response->failed()) {
                return ['error' => 'deepseek_failed'];
            }

            $content = $response->json('choices.0.message.content');
            return $this->decodeAiJson($content, 'deepseek');

        } catch (\Throwable $e) {
            return ['error' => 'deepseek_exception'];
        }
    }

    protected function callOpenRouter(string $prompt): array
    {
        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . config('services.openrouter.key')
            ])->post(config('services.openrouter.url'), [
                'model' => config('services.openrouter.model'),
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
            ]);
            Log::info("Openrouter response: {$response}");

            if ($response->failed()) {
                return ['error' => 'openrouter_failed'];
            }

            $content = $response->json('choices.0.message.content');
            return $this->decodeAiJson($content, 'openrouter');

        } catch (\Throwable $e) {
            return ['error' => 'openrouter_exception'];
        }
    }

    protected function decodeAiJson($content, string $provider): array
    {
        if (!is_string($content) || trim($content) === '') {
            return ['error' => "{$provider}_empty_content"];
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return ['error' => "{$provider}_json_decode_error", 'raw' => $content];
    }
}
