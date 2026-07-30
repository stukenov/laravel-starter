<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password as promptPassword;

/**
 * RULE(access): permissions are code, roles are data, and somebody has to
 * hold the first one — AGENTS.md.
 *
 * Without this the panel is unreachable on a fresh install. Roles are
 * seeded, permissions are declared in code, and nothing anywhere hands a
 * role to a person: every policy denies, `/dash` answers 403, and the
 * only way in is tinker. That is not a starter, it is a locked room.
 *
 *     php artisan user:admin ops@example.com
 *
 * Promotes the account if it exists, creates it if it does not. Safe to
 * run twice — a second run only re-grants the role.
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'user:admin
        {email : Who to make an administrator}
        {--name= : Display name, asked for when the account is new}
        {--password= : Non-interactive password, for a deploy script}';

    protected $description = 'Grant the admin role to a user, creating the account if needed';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->components->error("Not an email address: {$email}");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $user = $this->create($email);

            if ($user === null) {
                return self::FAILURE;
            }
        }

        // Recorded with no causer: this is run from a shell, not by a
        // signed-in person. See User::syncRolesAudited().
        $user->syncRolesAudited([RoleSeeder::ADMIN]);

        $this->components->info("{$email} is an administrator.");

        // Said out loud because it is the usual reason a fresh admin
        // cannot get in: the panel needs a verified address.
        if (! $user->hasVerifiedEmail()) {
            $this->components->warn('The address is not verified yet, so the panel will ask for that first.');
        }

        return self::SUCCESS;
    }

    private function create(string $email): ?User
    {
        $password = (string) ($this->option('password') ?: promptPassword(
            label: 'Password for the new administrator',
            required: true,
        ));

        $check = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::defaults()]],
        );

        if ($check->fails()) {
            $this->components->error($check->errors()->first('password'));

            return null;
        }

        // The model casts password to 'hashed', so the plain string is
        // what to assign — hashing here would hash it twice.
        $user = User::create([
            'name' => (string) ($this->option('name') ?: Str::before($email, '@')),
            'email' => $email,
            'password' => $password,
            'locale' => config('app.locale'),
            'timezone' => config('app.timezone') === 'UTC' ? 'Asia/Almaty' : config('app.timezone'),
        ]);

        // Not through create(): email_verified_at is not fillable, and
        // mass assignment drops it silently. Somebody running this command
        // on a server has proved the address more convincingly than a
        // mail link would.
        $user->markEmailAsVerified();

        return $user;
    }
}
