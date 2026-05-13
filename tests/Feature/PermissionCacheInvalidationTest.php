<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_sync_clears_cached_permissions(): void
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'view-dashboard']);
        $role = Role::create(['name' => 'Manager']);
        $role->givePermissionTo($permission->id);

        $this->assertFalse($user->hasPermission('view-dashboard'));

        $user->syncRoles([$role->id]);

        $this->assertTrue($user->fresh()->hasPermission('view-dashboard'));
    }

    public function test_role_permission_sync_refreshes_all_attached_users(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Support']);
        $user->syncRoles([$role->id]);

        $user->hasPermission('manage-tickets');

        $permission = Permission::create(['name' => 'manage-tickets']);
        $role->syncPermissions([$permission->id]);

        $this->assertTrue($user->fresh()->hasPermission('manage-tickets'));
    }

    public function test_role_delete_clears_attached_user_cache(): void
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'export-data']);
        $role = Role::create(['name' => 'Auditor']);
        $role->syncPermissions([$permission->id]);
        $user->syncRoles([$role->id]);

        $this->assertTrue($user->hasPermission('export-data'));

        $role->delete();

        $this->assertFalse($user->fresh()->hasPermission('export-data'));
    }
}
