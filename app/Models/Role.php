<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use Searchable;

    protected array $affectedUserIds = [];

    protected $fillable = ['name', 'guard_name'];

    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            $role->affectedUserIds = $role->users()->pluck('users.id')->all();
        });

        static::deleted(function (Role $role) {
            $role->clearUsersPermissionCache($role->affectedUserIds);
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }
        $this->permissions()->syncWithoutDetaching($permission);
        $this->refreshUsersCache();
    }

    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }
        $this->permissions()->detach($permission);
        $this->refreshUsersCache();
    }

    public function refreshUsersCache()
    {
        $this->clearUsersPermissionCache();
    }

    public function syncPermissions($permissions)
    {
        $this->permissions()->sync($permissions);
        $this->refreshUsersCache();
    }

    public function detachAllPermissions(): void
    {
        $this->permissions()->detach();
        $this->refreshUsersCache();
    }

    protected function clearUsersPermissionCache(array $userIds = []): void
    {
        $users = empty($userIds)
            ? $this->users()->get()
            : User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $user->clearPermissionsCache();
        }
    }
}
