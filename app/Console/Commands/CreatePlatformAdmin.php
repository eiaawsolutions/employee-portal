<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Bootstraps the first EIAAW platform admin user. Idempotent: re-running
 * with the same email rotates the password to a new random one.
 *
 * Usage:
 *   php artisan eiaaw:create-platform-admin <email> [--name="Amos"] [--password=...]
 *
 * Prints the password to stdout exactly once. Operator must save it.
 */
class CreatePlatformAdmin extends Command
{
    protected $signature = 'eiaaw:create-platform-admin
        {email : Work email — must match an entry in EIAAW_PLATFORM_ADMINS}
        {--name= : Display name (default: derived from email)}
        {--password= : Password (default: random 24-char base64)}';

    protected $description = 'Create or reset the first EIAAW platform admin user.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = $this->option('name') ?: ucfirst(Str::before($email, '@'));
        $password = $this->option('password') ?: Str::random(24);

        $user = User::firstOrNew(['work_email' => $email]);
        $user->name = $name;
        $user->role = 'system_admin';
        $user->is_active = true;
        $user->password = $password;
        $user->tenant_id = null;
        $user->save();

        $this->info('--------------------------------------------------------');
        $this->info(' EIAAW Platform Admin account ready');
        $this->info('--------------------------------------------------------');
        $this->line('  Email:    ' . $email);
        $this->line('  Password: ' . $password);
        $this->line('  Role:     system_admin');
        $this->info('--------------------------------------------------------');
        $this->warn(' Save this password — it will not be shown again.');
        $this->warn(' On first login you will be prompted to set up 2FA.');
        $this->info('--------------------------------------------------------');

        return self::SUCCESS;
    }
}
