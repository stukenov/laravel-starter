<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Access\Ability;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

// ── Correct data ─────────────────────────────────────────────────────

it('seeds every permission declared in code', function () {
    // The seeder runs on every deploy, so a permission added in code
    // appears without anyone remembering to create it by hand.
    foreach (Ability::all() as $name) {
        expect(Permission::where('name', $name)->exists())->toBeTrue($name);
    }
});

it('gives the admin role every permission', function () {
    $admin = Role::where('name', RoleSeeder::ADMIN)->first();

    expect($admin->permissions)->toHaveCount(count(Ability::all()));
});

it('gives a member no permissions at all', function () {
    // Registration is open. Handing a fresh account anything by default
    // is exactly how an open registration becomes an open panel.
    $member = Role::where('name', RoleSeeder::MEMBER)->first();

    expect($member->permissions)->toBeEmpty();
});

it('lets an administrator into the panel', function () {
    $user = User::factory()->admin()->create();

    expect($user->can(Ability::VIEW_USERS))->toBeTrue()
        ->and($user->can(Ability::MANAGE_USERS))->toBeTrue();
});

it('keeps roles and permissions on uuid keys', function () {
    // The published spatie migration uses bigint ids and an
    // unsignedBigInteger morph key pointing at users.id, which is a uuid
    // here. Mixed key types only surface when a relation is finally used.
    $types = DB::select("
        select table_name, column_name, data_type from information_schema.columns
        where (table_name = 'roles' and column_name = 'id')
           or (table_name = 'permissions' and column_name = 'id')
           or (table_name = 'model_has_roles' and column_name = 'model_id')
    ");

    foreach ($types as $column) {
        expect($column->data_type)->toBe('uuid', "{$column->table_name}.{$column->column_name}");
    }
});

it('binds a role to a user through the uuid pivot', function () {
    $user = User::factory()->create();

    $user->assignRole(RoleSeeder::ADMIN);

    expect($user->fresh()->hasRole(RoleSeeder::ADMIN))->toBeTrue();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('denies a user with no role', function () {
    $user = User::factory()->create();

    expect($user->can(Ability::VIEW_USERS))->toBeFalse()
        ->and($user->can(Ability::MANAGE_USERS))->toBeFalse();
});

it('denies a member every panel ability', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleSeeder::MEMBER);

    foreach (Ability::all() as $name) {
        expect($user->can($name))->toBeFalse($name);
    }
});

it('refuses to delete the admin role', function () {
    // Deleting it leaves nobody able to grant it back, and the panel locks
    // everyone out for good.
    $admin = User::factory()->admin()->create();
    $role = Role::where('name', RoleSeeder::ADMIN)->first();

    expect($admin->can('delete', $role))->toBeFalse();
});

it('denies an unknown ability even to an administrator', function () {
    $user = User::factory()->admin()->create();

    expect($user->can('something.invented'))->toBeFalse();
});

// ── Empty data ───────────────────────────────────────────────────────

it('is idempotent when seeded twice', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe(2)
        ->and(Permission::count())->toBe(count(Ability::all()));
});

it('leaves a user with no roles by default', function () {
    expect(User::factory()->create()->roles)->toBeEmpty();
});

it('survives checking an ability with an empty name', function () {
    $user = User::factory()->admin()->create();

    expect($user->can(''))->toBeFalse();
});
