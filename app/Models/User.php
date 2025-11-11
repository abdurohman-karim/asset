<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, Searchable, HasApiTokens;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'password_expires_at',
        'is_admin'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'is_admin'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function clearCacheData()
    {
        Cache::forget('user_roles_'.$this->id);
        Cache::forget('user_permissions_'.$this->id);
        Cache::forget('auth_user_'.$this->id);
    }

    public function switchTheme()
    {
        $this->theme = $this->theme == 'light' ? 'dark':'light';
        $this->save();
        return $this->theme;
    }
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasRole($role)
    {
        $roles = $this->getCachedRoles();
        if (is_string($role)) {
            return $roles->contains($role);
        }

        return $roles->contains($role->name);
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->getCachedPermissions(), true) || $this->hasRole('Super Admin');
    }

    public function assignRole(Role $role)
    {
        $this->roles()->syncWithoutDetaching($role->id);
        $this->clearPermissionsCache();
    }
    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }
        $this->permissions()->syncWithoutDetaching($permission);
        $this->clearPermissionsCache();
    }

    public function getCachedRoles()
    {
        return Cache::remember("user_roles_{$this->id}", 3600, function () {
            return $this->roles()->pluck('name');
        });
    }

    public function getCachedPermissions()
    {
        return Cache::remember("user_permissions_{$this->id}", 3600, function () {
            $this->loadMissing('roles.permissions');

            $permissions = $this->permissions->pluck('name');

            $rolePermissions = $this->roles->flatMap(function ($role) {
                return $role->permissions->pluck('name');
            });

            return $permissions->merge($rolePermissions)->unique()->toArray();
        });
    }
    public function clearPermissionsCache()
    {
        Cache::forget("user_roles_{$this->id}");
        Cache::forget("user_permissions_{$this->id}");
    }
    public function assignRoles(...$roles)
    {
        $roles = collect($roles)->flatten()->map(function ($role) {
            return $this->getRoleId($role);
        })->all();

        $this->roles()->syncWithoutDetaching($roles);
        $this->clearPermissionsCache();
    }
    public function removeRoles(...$roles)
    {
        $roles = collect($roles)->flatten()->map(function ($role) {
            return $this->getRoleId($role);
        })->all();

        $this->roles()->detach($roles);
        $this->clearPermissionsCache();
    }

    protected function getRoleId($role)
    {
        if (is_numeric($role)) {
            return $role;
        }
        return Role::where('name', $role)->firstOrFail()->id;
    }

    public function givePermissions(...$permissions)
    {
        $permissions = collect($permissions)->flatten()->map(function ($permission) {
            return $this->getPermissionId($permission);
        })->all();

        $this->permissions()->syncWithoutDetaching($permissions);
        $this->clearPermissionsCache();
    }

    public function revokePermissions(...$permissions)
    {
        $permissions = collect($permissions)->flatten()->map(function ($permission) {
            return $this->getPermissionId($permission);
        })->all();

        $this->permissions()->detach($permissions);
        $this->clearPermissionsCache();
    }

    // Helper method to get permission ID
    protected function getPermissionId($permission)
    {
        if (is_numeric($permission)) {
            return $permission;
        }
        return Permission::where('name', $permission)->firstOrFail()->id;
    }
}
