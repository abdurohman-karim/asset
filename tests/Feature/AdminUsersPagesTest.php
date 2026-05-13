<?php

namespace Tests\Feature;

use App\Models\AiInsight;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\GoalPayment;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_users_index(): void
    {
        $admin = $this->makeSuperAdmin();
        $targetUser = User::factory()->create([
            'name' => 'Visible User',
            'phone' => '901234567',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Visible User');
        $response->assertSee((string) $targetUser->id);
    }

    public function test_super_admin_can_access_user_show_page(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = User::factory()->create([
            'name' => 'Detailed User',
            'phone' => '911111111',
            'settings' => ['theme' => 'dark'],
            'language' => 'en',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'amount' => 2500,
            'category' => 'Salary',
            'description' => 'Monthly payout',
            'datetime' => now()->subDay(),
            'raw' => ['source' => 'bank'],
        ]);

        Budget::create([
            'user_id' => $user->id,
            'month' => now()->format('Y-m'),
            'income' => 2500,
            'expenses' => 700,
            'recommended_daily_limit' => 60,
            'categories' => ['food' => 300],
        ]);

        $goal = Goal::create([
            'user_id' => $user->id,
            'title' => 'Laptop',
            'amount_total' => 5000,
            'amount_saved' => 1200,
            'deadline' => now()->addMonths(3),
            'status' => 'active',
        ]);

        GoalPayment::create([
            'goal_id' => $goal->id,
            'amount' => 400,
            'method' => 'manual',
        ]);

        AiInsight::create([
            'user_id' => $user->id,
            'type' => 'analysis',
            'insight' => 'Spending is stable.',
            'metadata' => ['period' => 'month'],
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertOk();
        $response->assertSee('Detailed User');
        $response->assertSee('Laptop');
        $response->assertSee('Spending is stable.');
    }

    public function test_normal_user_cannot_access_admin_users_pages(): void
    {
        $user = User::factory()->create([
            'phone' => '922222222',
        ]);
        $targetUser = User::factory()->create([
            'phone' => '933333333',
        ]);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('users.show', $targetUser))
            ->assertForbidden();
    }

    public function test_users_index_search_filters_results(): void
    {
        $admin = $this->makeSuperAdmin();

        User::factory()->create([
            'name' => 'Alice Searchable',
            'email' => 'alice@example.com',
            'phone' => '944444444',
            'tg_user_id' => 'tg-alice',
        ]);

        User::factory()->create([
            'name' => 'Bob Hidden',
            'email' => 'bob@example.com',
            'phone' => '955555555',
            'tg_user_id' => 'tg-bob',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'search' => 'tg-alice',
        ]));

        $response->assertOk();
        $response->assertSee('Alice Searchable');
        $response->assertDontSee('Bob Hidden');
    }

    public function test_user_show_page_renders_cleanly_without_related_data(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = User::factory()->create([
            'name' => 'Empty User',
            'phone' => '966666666',
        ]);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertOk();
        $response->assertSee('Empty User');
        $response->assertSee('У пользователя пока нет транзакций.');
        $response->assertSee('Цели пользователя отсутствуют.');
        $response->assertSee('AI insights для пользователя отсутствуют.');
    }

    private function makeSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);
        $user = User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'super-admin@example.com',
            'phone' => '900000000',
            'is_admin' => true,
        ]);

        $user->syncRoles([$role->id]);

        return $user;
    }
}
