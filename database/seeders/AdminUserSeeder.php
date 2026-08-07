<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Creates the single administrator account, from the environment only.
 *
 * Both ADMIN_EMAIL and ADMIN_PASSWORD are required. The seeder deliberately
 * does not fall back to a default password, and does not generate one either:
 * a default is guessable from the repository, and a generated one has to be
 * printed or logged to be usable — writing a live credential into a log file or
 * a CI transcript is worse than not creating the account at all.
 *
 * Missing credentials are therefore fatal in production, where deploying
 * without an admin is a real failure, and a quiet skip everywhere else, so CI
 * and a fresh clone can seed without secrets.
 */
class AdminUserSeeder extends Seeder
{
    public const string ROLE = 'admin';

    public function run(): void
    {
        // Roles are part of the schema's contract even when no admin exists, so
        // session 3 can scope token abilities against a role that is always there.
        $role = Role::findOrCreate(self::ROLE, 'web');

        $email = (string) config('portfolio.admin.email');
        $password = (string) config('portfolio.admin.password');

        if ($email === '' || $password === '') {
            $message = 'ADMIN_EMAIL and ADMIN_PASSWORD must both be set to seed the admin user.';

            if (App::isProduction()) {
                throw new RuntimeException($message);
            }

            Log::info($message.' Skipping AdminUserSeeder.');

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('portfolio.admin.name'),
                // Not updateOrCreate: re-running the seeder must not silently
                // reset a password that has since been rotated.
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        // assignRole is safe to repeat, but it still writes the pivot row every
        // time, which would make a re-seed a write rather than a no-op.
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
