<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('datetime')
            ->get();

        $goal = $this->getActiveGoal($user);

        $todayTransactions = $transactions->map(fn ($t) => [
            'amount' => (float) $t->amount,
            'category' => $t->category,
            'description' => $t->description,
            'datetime' => $t->datetime?->toDateTimeString(),
        ])->values()->all();

        $context = $this->buildContextPackage($user, $goal, [
            'analysis_start' => $today,
            'analysis_end' => $today,
            'today_transactions' => $todayTransactions,
        ]);

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('daily_insight', $user, $context, [
            'Compare today with month-to-date averages.',
            'Highlight any spike or unusual category behavior.',
            'Give one clear next-day action.',
        ]);

        $ai = $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);

        $insight = $ai['summary'] ?? $this->fallbackResponse($language)['summary'];

        return [
            'insight' => $insight,
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
            return $this->fallbackResponse($this->resolveLanguage($user));
        }

        Cache::put($cacheKey, true, now()->endOfDay());

        $context = $this->buildContextPackage($user, $goal);

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('goal_analysis', $user, $context, [
            'Assess goal progress vs target and deadline.',
            'Explain how current spending patterns affect the goal.',
            'Give 1–2 practical next steps to stay on track.',
        ]);

        return $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);
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
            ->orderByDesc('datetime')
            ->limit(12)
            ->get()
            ->map(fn ($t) => [
                'amount' => (float) $t->amount,
                'category' => $t->category,
                'description' => $t->description,
                'datetime' => $t->datetime?->toDateTimeString(),
            ])->values()->all();

        $goal = $this->getActiveGoal($user);

        $context = $this->buildContextPackage($user, $goal, [
            'analysis_start' => $from,
            'analysis_end' => $to,
            'recent_transactions' => $transactions,
        ]);

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('transaction_analysis', $user, $context, [
            "Analyze the period {$from->toDateString()} to {$to->toDateString()}.",
            'Identify trends, category drivers, and any behavioral pattern.',
            'Provide 1–2 concrete, realistic actions.',
        ]);

        return $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);
    }

    public function weeklyReview(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $today = Carbon::today();
        $from = $today->copy()->subDays(6);

        $goal = $this->getActiveGoal($user);

        $context = $this->buildContextPackage($user, $goal, [
            'analysis_start' => $from,
            'analysis_end' => $today,
        ]);

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('weekly_review', $user, $context, [
            'Summarize the last 7 days with one win and one risk.',
            'Connect weekly behavior to month goals.',
            'Give one actionable focus for the next week.',
        ]);

        return $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);
    }

    public function riskDetection(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $today = Carbon::today();
        $from = $today->copy()->subDays(30);

        $goal = $this->getActiveGoal($user);

        $context = $this->buildContextPackage($user, $goal, [
            'analysis_start' => $from,
            'analysis_end' => $today,
        ]);

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('risk_detection', $user, $context, [
            'Detect overspending risk using spikes, category concentration, and expense delta.',
            'State the risk level and the most likely drivers.',
            'Give one immediate corrective action.',
        ]);

        return $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);
    }

    public function savingsProjection(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $goal = $this->getActiveGoal($user);
        $context = $this->buildContextPackage($user, $goal);

        $monthlyIncome = (float) ($context['month']['income'] ?? 0);
        $monthlyExpense = (float) ($context['month']['expense'] ?? 0);
        $daysLeft = (int) ($context['month']['days_left'] ?? 0);
        $daysElapsed = max(1, Carbon::today()->day);

        $netSoFar = $monthlyIncome - $monthlyExpense;
        $avgDailyNet = $daysElapsed > 0 ? round($netSoFar / $daysElapsed, 2) : 0.0;
        $projectedNet = round($netSoFar + ($avgDailyNet * $daysLeft), 2);

        $context['projection'] = [
            'net_so_far' => (float) $netSoFar,
            'avg_daily_net' => (float) $avgDailyNet,
            'projected_month_net' => (float) $projectedNet,
            'days_elapsed' => $daysElapsed,
        ];

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('savings_projection', $user, $context, [
            'Project end-of-month savings based on current pace.',
            'Explain confidence and key assumptions.',
            'Suggest one adjustment to improve the projection.',
        ]);

        return $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);
    }

    public function predictiveBalance(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $goal = $this->getActiveGoal($user);
        $context = $this->buildContextPackage($user, $goal);

        $monthlyIncome = (float) ($context['month']['income'] ?? 0);
        $monthlyExpense = (float) ($context['month']['expense'] ?? 0);
        $daysLeft = (int) ($context['month']['days_left'] ?? 0);
        $daysElapsed = max(1, Carbon::today()->day);

        $avgDailyIncome = $daysElapsed > 0 ? round($monthlyIncome / $daysElapsed, 2) : 0.0;
        $avgDailyExpense = $daysElapsed > 0 ? round($monthlyExpense / $daysElapsed, 2) : 0.0;

        $projectedIncome = round($monthlyIncome + ($avgDailyIncome * $daysLeft), 2);
        $projectedExpense = round($monthlyExpense + ($avgDailyExpense * $daysLeft), 2);
        $projectedNet = round($projectedIncome - $projectedExpense, 2);

        $context['projection'] = [
            'projected_income' => (float) $projectedIncome,
            'projected_expense' => (float) $projectedExpense,
            'projected_month_net' => (float) $projectedNet,
            'avg_daily_income' => (float) $avgDailyIncome,
            'avg_daily_expense' => (float) $avgDailyExpense,
            'days_elapsed' => $daysElapsed,
        ];

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('predictive_balance', $user, $context, [
            'Estimate end-of-month balance trend using current pace.',
            'Flag any risk to goal progress or budget stability.',
            'Give one concrete action to improve stability.',
        ]);

        return $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);
    }

    public function behavioralProfile(array $params, ?User $user = null): array
    {
        if (!$user) {
            throw new \RuntimeException('User required');
        }

        $goal = $this->getActiveGoal($user);
        $context = $this->buildContextPackage($user, $goal);

        $language = $this->resolveLanguage($user);
        $prompt = $this->buildPrompt('behavioral_profile', $user, $context, [
            'Describe spending habits and behavioral patterns.',
            'Mention one strength and one risk pattern.',
            'Suggest one behavior-level tweak.',
        ]);

        return $this->callLLM($this->buildSystemPrompt($language), $prompt, $language);
    }

    protected function getActiveGoal(User $user): ?Goal
    {
        return Goal::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByDesc('is_primary')
            ->orderBy('priority')
            ->first();
    }

    protected function buildPrompt(string $task, User $user, array $context, array $focus = []): string
    {
        $language = $this->resolveLanguage($user);
        $payload = [
            'task' => $task,
            'language' => $language,
            'section_titles' => $this->summarySectionTitles($language),
            'recommendation_title' => $this->recommendationTitle($language),
            'context' => $context,
        ];

        if (!empty($focus)) {
            $payload['focus'] = array_values($focus);
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    protected function buildSystemPrompt(string $language): string
    {
        $languageName = $this->languageName($language);

        return "You are a premium fintech financial advisor.\n"
            . "Tone: calm, confident, concise, high-trust. No fluff, no slang.\n"
            . "You MUST answer strictly in the user language: {$languageName} (code: {$language}).\n"
            . "Use only UZS currency.\n"
            . "Return ONLY valid JSON. No markdown, no extra text.\n"
            . "In 'summary', use the provided section_titles exactly as headings, in order."
            . " Each section must be 1–3 short sentences.\n"
            . "Do NOT include any recommendation section inside summary.\n"
            . "Reference trends, deltas, and spikes if present. If data is missing, state it briefly.\n"
            . "'recommendation' must be 1–2 actionable steps without headings.\n"
            . "No emoji overload; only the emojis from section titles.";
    }

    protected function buildContextPackage(User $user, ?Goal $goal, array $options = []): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $todayEnd = $today->copy()->endOfDay();

        $language = $this->resolveLanguage($user);

        [$monthlyIncome, $monthlyExpense] = $this->getTotals($user, $monthStart, $todayEnd);

        $lastMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();
        [$lastMonthIncome, $lastMonthExpense] = $this->getTotals($user, $lastMonthStart, $lastMonthEnd);

        $trackingDays = $this->getTrackingDays($user, $monthStart, $todayEnd);
        $avgDailyExpense = $trackingDays > 0 ? round($monthlyExpense / $trackingDays, 2) : 0.0;
        $daysLeft = max(0, $today->diffInDays($monthEnd));

        $expenseDeltaPct = null;
        if ($lastMonthExpense > 0) {
            $expenseDeltaPct = round((($monthlyExpense - $lastMonthExpense) / $lastMonthExpense) * 100, 2);
        }

        $topCategories = $this->getTopCategories($user, $monthStart, $todayEnd, 3);

        $recentStart = $today->copy()->subDays(14)->startOfDay();
        $recentDaily = $this->getDailyExpenseSeries($user, $recentStart, $todayEnd);
        $recentSpikes = $this->getRecentSpikes($recentDaily, $avgDailyExpense);

        $context = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'language' => $language,
            ],
            'month' => [
                'start' => $monthStart->toDateString(),
                'end' => $monthEnd->toDateString(),
                'days_left' => $daysLeft,
                'tracking_days' => $trackingDays,
                'income' => (float) $monthlyIncome,
                'expense' => (float) $monthlyExpense,
                'avg_daily_expense' => (float) $avgDailyExpense,
            ],
            'last_month' => [
                'start' => $lastMonthStart->toDateString(),
                'end' => $lastMonthEnd->toDateString(),
                'income' => (float) $lastMonthIncome,
                'expense' => (float) $lastMonthExpense,
                'expense_delta_pct' => $expenseDeltaPct,
            ],
            'top_categories' => $topCategories,
            'recent_spikes' => $recentSpikes,
            'recent_spikes_window_days' => 14,
        ];

        if ($goal) {
            $context['goal'] = [
                'title' => $goal->title,
                'amount_total' => (float) $goal->amount_total,
                'amount_saved' => (float) $goal->amount_saved,
                'progress_pct' => (float) $goal->progress,
                'deadline' => optional($goal->deadline)->toDateString(),
                'is_primary' => (bool) $goal->is_primary,
            ];
        } else {
            $context['goal'] = null;
        }

        if (!empty($options['analysis_start']) && !empty($options['analysis_end'])) {
            $analysisStart = $options['analysis_start']->copy()->startOfDay();
            $analysisEnd = $options['analysis_end']->copy()->endOfDay();
            [$analysisIncome, $analysisExpense] = $this->getTotals($user, $analysisStart, $analysisEnd);
            $analysisDailyExpense = $this->getDailyExpenseSeries($user, $analysisStart, $analysisEnd);
            $analysisTopCategories = $this->getTopCategories($user, $analysisStart, $analysisEnd, 3);
            $analysisDays = $analysisStart->copy()->startOfDay()->diffInDays($analysisEnd->copy()->startOfDay()) + 1;

            $context['analysis'] = [
                'start' => $analysisStart->toDateString(),
                'end' => $analysisEnd->toDateString(),
                'days' => $analysisDays,
                'income' => (float) $analysisIncome,
                'expense' => (float) $analysisExpense,
                'daily_expense' => $analysisDailyExpense,
                'top_categories' => $analysisTopCategories,
            ];
        }

        if (!empty($options['today_transactions'])) {
            $context['today_transactions'] = $options['today_transactions'];
        }

        if (!empty($options['recent_transactions'])) {
            $context['recent_transactions'] = $options['recent_transactions'];
        }

        if (!empty($options['projection'])) {
            $context['projection'] = $options['projection'];
        }

        return $context;
    }

    protected function resolveLanguage(?User $user): string
    {
        $language = strtolower((string) ($user?->language ?? 'ru'));

        if (!in_array($language, ['ru', 'uz', 'en'], true)) {
            return 'ru';
        }

        return $language;
    }

    protected function languageName(string $language): string
    {
        return match ($language) {
            'en' => 'English',
            'uz' => 'Uzbek',
            default => 'Russian',
        };
    }

    protected function summarySectionTitles(string $language): array
    {
        return match ($language) {
            'en' => [
                'current' => '📊 Current Situation',
                'trends' => '📈 Trends',
                'goal' => '🎯 Goal Impact',
            ],
            'uz' => [
                'current' => '📊 Hozirgi holat',
                'trends' => '📈 Trendlar',
                'goal' => '🎯 Maqsadga ta\'sir',
            ],
            default => [
                'current' => '📊 Текущая ситуация',
                'trends' => '📈 Тренды',
                'goal' => '🎯 Влияние на цели',
            ],
        };
    }

    protected function recommendationTitle(string $language): string
    {
        return match ($language) {
            'en' => '💡 Recommendation',
            'uz' => '💡 Tavsiya',
            default => '💡 Рекомендация',
        };
    }

    protected function fallbackResponse(string $language): array
    {
        return match ($language) {
            'en' => [
                'summary' => "📊 Current Situation\nInsights are temporarily unavailable.\n\n📈 Trends\nNot enough data to analyze trends right now.\n\n🎯 Goal Impact\nGoal impact cannot be assessed at the moment.\n\n💡 Recommendation\nPlease try again a bit later.",
                'recommendation' => 'Try again later.',
                'numbers' => ['score' => 0.0],
                'provider' => 'fallback'
            ],
            'uz' => [
                'summary' => "📊 Hozirgi holat\nHozircha AI tahlili mavjud emas.\n\n📈 Trendlar\nTrendlarni baholash uchun ma'lumot yetarli emas.\n\n🎯 Maqsadga ta'sir\nMaqsadga ta'sirni hozir baholash imkoni yo'q.\n\n💡 Tavsiya\nBirozdan keyin yana urinib ko'ring.",
                'recommendation' => "Birozdan keyin yana urinib ko'ring.",
                'numbers' => ['score' => 0.0],
                'provider' => 'fallback'
            ],
            default => [
                'summary' => "📊 Текущая ситуация\nСейчас AI-совет недоступен.\n\n📈 Тренды\nНедостаточно данных для анализа трендов.\n\n🎯 Влияние на цели\nОценка влияния пока недоступна.\n\n💡 Рекомендация\nПопробуйте ещё раз немного позже.",
                'recommendation' => 'Попробуйте позже.',
                'numbers' => ['score' => 0.0],
                'provider' => 'fallback'
            ],
        };
    }

    protected function getTotals(User $user, Carbon $start, Carbon $end): array
    {
        $income = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '>', 0)
            ->sum('amount');

        $expense = Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '<', 0)
            ->sum('amount');

        return [
            (float) $income,
            (float) abs($expense),
        ];
    }

    protected function getTopCategories(User $user, Carbon $start, Carbon $end, int $limit = 3): array
    {
        $rows = Transaction::selectRaw('category, SUM(ABS(amount)) as total')
            ->where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '<', 0)
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => [
            'category' => $row->category,
            'amount' => (float) $row->total,
        ])->values()->all();
    }

    protected function getDailyExpenseSeries(User $user, Carbon $start, Carbon $end): array
    {
        $rows = Transaction::selectRaw('DATE(datetime) as date, SUM(ABS(amount)) as total')
            ->where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->where('amount', '<', 0)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $rows->map(fn ($row) => [
            'date' => $row->date,
            'expense' => (float) $row->total,
        ])->values()->all();
    }

    protected function getTrackingDays(User $user, Carbon $start, Carbon $end): int
    {
        return (int) Transaction::where('user_id', $user->id)
            ->whereBetween('datetime', [$start, $end])
            ->selectRaw('DATE(datetime) as day')
            ->distinct()
            ->get()
            ->count();
    }

    protected function getRecentSpikes(array $dailySeries, float $avgDailyExpense, int $limit = 3): array
    {
        if ($avgDailyExpense <= 0) {
            return [];
        }

        $threshold = $avgDailyExpense * 1.6;
        $spikes = array_filter($dailySeries, function ($row) use ($threshold) {
            return ($row['expense'] ?? 0) >= $threshold;
        });

        usort($spikes, function ($a, $b) {
            return ($b['expense'] ?? 0) <=> ($a['expense'] ?? 0);
        });

        return array_slice(array_values($spikes), 0, $limit);
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

    protected function callLLM(string $systemPrompt, string $userPrompt, string $language): array
    {
        while ($model = $this->getActiveModel()) {

            $method = "call" . ucfirst($model);

            try {
                $result = $this->$method($systemPrompt, $userPrompt);

                if (!$this->isAiError($result)) {
                    Cache::increment("llm_used_{$model}");
                    return $this->normalizeAiResponse($result, $language);
                }

                Cache::increment("llm_fail_{$model}");

            } catch (\Throwable $e) {
                Cache::increment("llm_fail_{$model}");
            }
        }

        return $this->fallbackResponse($language);
    }

    protected function isAiError(array $result): bool
    {
        return isset($result['error']);
    }

    protected function callOpenAI(string $systemPrompt, string $userPrompt): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4.1-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    ['role' => 'user', 'content' => $userPrompt],
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

    protected function callGroq(string $systemPrompt, string $userPrompt): array
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
                        'content' => $systemPrompt,
                    ],
                    ['role' => 'user', 'content' => $userPrompt],
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

    protected function callDeepSeek(string $systemPrompt, string $userPrompt): array
    {
        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . config('services.deepseek.key')
            ])->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    ['role' => 'user', 'content' => $userPrompt],
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

    protected function callOpenRouter(string $systemPrompt, string $userPrompt): array
    {
        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . config('services.openrouter.key')
            ])->post(config('services.openrouter.url'), [
                'model' => config('services.openrouter.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    ['role' => 'user', 'content' => $userPrompt],
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

    protected function normalizeAiResponse(array $result, string $language): array
    {
        if ($this->isAiError($result)) {
            return $result;
        }

        $sectionTitles = $this->summarySectionTitles($language);
        $recommendationTitle = $this->recommendationTitle($language);

        $summary = $this->extractSummary($result, $sectionTitles);
        if (!$summary) {
            $fallback = $this->fallbackResponse($language);
            $result['summary'] = $fallback['summary'];
            $result['recommendation'] = $this->toPlainString($result['recommendation'] ?? null) ?: $fallback['recommendation'];
            $result['numbers'] = $result['numbers'] ?? ($fallback['numbers'] ?? null);
            return $result;
        }

        $result['summary'] = $this->stripRecommendationSection($summary, $recommendationTitle);

        $recommendation = $this->toPlainString($result['recommendation'] ?? null);
        if (!$recommendation) {
            $recommendation = $this->toPlainString($result[$recommendationTitle] ?? null);
        }
        $result['recommendation'] = $recommendation;

        if (!array_key_exists('numbers', $result) || !is_array($result['numbers'])) {
            $result['numbers'] = null;
        }

        return $result;
    }

    protected function extractSummary(array $result, array $sectionTitles): string
    {
        $summary = $this->toPlainString($result['summary'] ?? null);
        if ($summary) {
            return $summary;
        }

        if (isset($result['summary']) && is_array($result['summary'])) {
            $summary = $this->buildSummaryFromMap($result['summary'], $sectionTitles);
            if ($summary) {
                return $summary;
            }
        }

        $summary = $this->buildSummaryFromMap($result, $sectionTitles);
        if ($summary) {
            return $summary;
        }

        $summary = $this->buildSummaryFromKeys($result, $sectionTitles);
        if ($summary) {
            return $summary;
        }

        return '';
    }

    protected function buildSummaryFromMap(array $map, array $sectionTitles): string
    {
        $sections = [];

        foreach ($sectionTitles as $key => $title) {
            if (!array_key_exists($title, $map)) {
                continue;
            }
            $text = $this->toPlainString($map[$title]);
            if ($text === '') {
                continue;
            }
            $sections[] = $title . "\n" . $text;
        }

        if (!empty($sections)) {
            return implode("\n\n", $sections);
        }

        return '';
    }

    protected function buildSummaryFromKeys(array $map, array $sectionTitles): string
    {
        $sections = [];

        foreach ($sectionTitles as $key => $title) {
            if (!array_key_exists($key, $map)) {
                continue;
            }
            $text = $this->toPlainString($map[$key]);
            if ($text === '') {
                continue;
            }
            $sections[] = $title . "\n" . $text;
        }

        if (!empty($sections)) {
            return implode("\n\n", $sections);
        }

        return '';
    }

    protected function toPlainString($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            $parts = [];
            foreach ($value as $key => $item) {
                $text = $this->toPlainString($item);
                if ($text === '') {
                    continue;
                }
                if (is_string($key)) {
                    $parts[] = $key . "\n" . $text;
                } else {
                    $parts[] = $text;
                }
            }
            return trim(implode("\n", $parts));
        }

        return '';
    }

    protected function stripRecommendationSection(string $summary, string $recommendationTitle): string
    {
        $summary = trim($summary);
        if ($summary === '') {
            return $summary;
        }

        $blocks = preg_split("/\\n\\n+/", $summary);
        if (!$blocks) {
            return $summary;
        }

        $filtered = [];
        foreach ($blocks as $block) {
            $trimmed = trim($block);
            if ($trimmed === '') {
                continue;
            }
            if (str_starts_with($trimmed, $recommendationTitle)) {
                continue;
            }
            $filtered[] = $trimmed;
        }

        return implode("\n\n", $filtered);
    }
}
