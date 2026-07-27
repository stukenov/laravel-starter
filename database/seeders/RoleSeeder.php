<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Access\Ability;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent on purpose: it runs on every deploy so a permission added in
 * code appears without anyone remembering to create it by hand.
 */
class RoleSeeder extends Seeder
{
    public const ADMIN = 'admin';

    public const MEMBER = 'member';

    public function run(): void
    {
        foreach (Ability::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // The registrar caches the permission list for the whole request.
        // It was loaded before these were created, so syncing against it
        // fails with PermissionDoesNotExist even though the rows exist.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate(self::ADMIN, 'web');
        $admin->syncPermissions(Ability::all());

        // A member has no panel access at all. Handing them anything by
        // default is how open registration turns into an open panel.
        Role::findOrCreate(self::MEMBER, 'web');
    }
}
