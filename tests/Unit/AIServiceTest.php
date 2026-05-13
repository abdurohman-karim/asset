<?php

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\User;
use App\Services\AIService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_analysis_caches_actual_response(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 13, 12));
        config()->set('cache.default', 'array');

        $user = User::factory()->create(['language' => 'en']);
        $goal = Goal::create([
            'user_id' => $user->id,
            'title' => 'Laptop',
            'amount_total' => 2000,
            'amount_saved' => 500,
            'priority' => 1,
            'status' => 'active',
        ]);

        $service = new class extends AIService {
            public int $calls = 0;

            protected function callLLM(string $systemPrompt, string $userPrompt, string $language): array
            {
                $this->calls++;

                return [
                    'summary' => '📊 Current Situation' . "\n" . 'On track'
                        . "\n\n" . '📈 Trends' . "\n" . 'Stable'
                        . "\n\n" . '🎯 Goal Impact' . "\n" . 'Positive',
                    'recommendation' => 'Keep going.',
                    'numbers' => ['score' => 0.9],
                    'provider' => 'fake',
                ];
            }
        };

        $first = $service->goalAnalysis(['goal_id' => $goal->id], $user);
        $second = $service->goalAnalysis(['goal_id' => $goal->id], $user);

        $this->assertSame($first, $second);
        $this->assertSame(1, $service->calls);

        Carbon::setTestNow();
    }

    public function test_provider_counters_expire(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 13, 12));
        config()->set('cache.default', 'array');
        config()->set('services.models_llm', [
            ['name' => 'openai', 'limit' => 1],
            ['name' => 'groq', 'limit' => 1],
        ]);

        $service = new class extends AIService {
            public function increment(string $key): void
            {
                $this->incrementProviderCounter($key);
            }

            public function activeModel(): ?string
            {
                return $this->getActiveModel();
            }
        };

        $service->increment('llm_used_openai');
        $this->assertSame('groq', $service->activeModel());

        Carbon::setTestNow(Carbon::now()->addSeconds(3601));

        $this->assertSame('openai', $service->activeModel());

        Carbon::setTestNow();
    }
}
