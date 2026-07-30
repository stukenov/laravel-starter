<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * RULE(tests): Playwright is the third level — AGENTS.md.
 *
 * Browser tests need a database in a known state and accounts they can log
 * into. Both are built here rather than in the Playwright config, so the
 * fixtures live next to the code that defines what a role is.
 */
class PrepareE2eCommand extends Command
{
    protected $signature = 'e2e:prepare';

    protected $description = 'Reset the end-to-end database and create its fixed accounts';

    public const PASSWORD = 'password';

    public const ADMIN = 'admin@e2e.test';

    public const MEMBER = 'member@e2e.test';

    public function handle(): int
    {
        $database = DB::connection()->getDatabaseName();

        // This command drops every table. The name is the only thing
        // standing between a stray `php artisan e2e:prepare` and somebody's
        // development data, so it is checked rather than trusted.
        if (! str_ends_with($database, '_e2e')) {
            $this->error("Refusing to run against '{$database}': the end-to-end database name must end with _e2e.");

            return self::FAILURE;
        }

        $this->ensureDatabaseExists($database);

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);

        $this->account(self::ADMIN, 'Админ', RoleSeeder::ADMIN);
        $this->account(self::MEMBER, 'Участник', RoleSeeder::MEMBER);

        $this->info("Database {$database} is ready.");

        return self::SUCCESS;
    }

    /**
     * Creates the database on first run, so setting the suite up is one
     * command rather than a step somebody has to read about first.
     *
     * Postgres has no CREATE DATABASE IF NOT EXISTS, hence the lookup. The
     * connection has to be made to a database that already exists, so it
     * goes through `postgres`.
     */
    private function ensureDatabaseExists(string $database): void
    {
        config(['database.connections.pgsql_root' => array_merge(
            Config::array('database.connections.pgsql'),
            ['database' => 'postgres'],
        )]);

        $root = DB::connection('pgsql_root');

        $exists = $root->selectOne('select 1 from pg_database where datname = ?', [$database]);

        if ($exists === null) {
            $root->statement('create database "'.$database.'"');
            $this->line("Created database {$database}.");
        }

        $root->disconnect();
    }

    private function account(string $email, string $name, string $role): void
    {
        // The model casts password to 'hashed', so the plain string is the
        // right thing to assign — hashing it here would hash it twice.
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => self::PASSWORD,
            'locale' => 'ru',
            'timezone' => 'Asia/Almaty',
        ]);

        // Not through create(): email_verified_at is not fillable, so mass
        // assignment drops it without a word. The account is then made,
        // signs in, and is bounced straight to /email/verify — which cost
        // a full end-to-end run to work out.
        $user->markEmailAsVerified();

        $user->syncRoles([$role]);
    }
}
