<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * RULE(demo): nothing that ships is demo content — AGENTS.md.
 *
 * The upstream starter seeded a "Test User" here. It reached every clone
 * that ran `migrate --seed`, including production: a real account, a
 * known address, and no role — so it could not even open the panel.
 *
 * Roles are infrastructure and are seeded on every deploy. People are
 * not: the first administrator is created deliberately, with
 * `php artisan user:admin`.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);
    }
}
