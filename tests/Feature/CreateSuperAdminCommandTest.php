<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_super_admin_user_and_role(): void
    {
        $this->artisan('admin:create-super', [
            '--email' => 'admin@example.com',
            '--name' => 'Main Admin',
        ])
            ->expectsOutput('Super Admin is ready: admin@example.com')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Main Admin', $user->name);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('12345678', $user->password));
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertSame(1, Role::where('name', 'Super Admin')->count());
    }

    public function test_command_updates_existing_user_without_duplication(): void
    {
        $user = User::factory()->create([
            'email' => 'existing-admin@example.com',
            'name' => 'Old Name',
            'password' => Hash::make('old-password'),
            'is_admin' => false,
        ]);

        $role = Role::create(['name' => 'Super Admin']);
        $user->syncRoles([$role->id]);
        $user->removeRoles($role->id);

        $this->artisan('admin:create-super', [
            '--email' => 'existing-admin@example.com',
            '--name' => 'Updated Admin',
        ])->assertExitCode(0);

        $this->assertSame(1, User::where('email', 'existing-admin@example.com')->count());

        $updatedUser = $user->fresh();

        $this->assertSame('Updated Admin', $updatedUser->name);
        $this->assertTrue($updatedUser->is_admin);
        $this->assertTrue(Hash::check('12345678', $updatedUser->password));
        $this->assertTrue($updatedUser->hasRole('Super Admin'));
        $this->assertSame(1, Role::where('name', 'Super Admin')->count());
    }
}
