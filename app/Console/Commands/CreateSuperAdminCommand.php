<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'admin:create
        {--email=admin@example.com : Super Admin email}
        {--name=Super Admin : Super Admin name}';

    protected $description = 'Create or update the Super Admin user.';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->option('email')));
        $name = trim((string) $this->option('name'));

        if ($email === '' || $name === '') {
            $this->error('Both --email and --name must be non-empty values.');

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($email, $name) {
            $user = User::withTrashed()->firstOrNew(['email' => $email]);

            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make('12345678');

            if (Schema::hasColumn('users', 'is_admin')) {
                $user->is_admin = true;
            }

            if ($user->exists && method_exists($user, 'trashed') && $user->trashed()) {
                $user->restore();
            }

            $user->save();

            if (Schema::hasTable('roles') && Schema::hasTable('role_user')) {
                $role = Role::firstOrCreate(
                    ['name' => 'Super Admin'],
                    ['guard_name' => 'web']
                );

                $user->syncRoles([$role->id]);
            }

            return $user->fresh(['roles']);
        });

        $this->info("Super Admin is ready: {$user->email}");
        $this->line("User ID: {$user->id}");
        $this->line('Password: 12345678');
        $this->line('Roles: '.($user->roles->pluck('name')->join(', ') ?: 'No roles assigned'));
        $this->line('Admin flag: '.($user->is_admin ? 'true' : 'false'));

        return self::SUCCESS;
    }
}
