<?php

use App\Models\User;
use App\Support\Access\Ability;
use Database\Seeders\RoleSeeder;

/**
 * RULE(access): roles are data, and somebody has to be able to hand them
 * out — AGENTS.md.
 *
 * The panel could define roles and their permissions but had no way to
 * give one to a person. Every policy denied, `/dash` answered 403, and
 * the only way in was tinker — a starter that shipped a locked room.
 */
function form(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'password' => '',
        'locale' => 'ru',
        'timezone' => 'Asia/Almaty',
    ], $overrides);
}

// ── Correct data ─────────────────────────────────────────────────────

it('gives a role to a user', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('dash.users.update', $target), form($target, [
            'roles_submitted' => '1',
            'roles' => [RoleSeeder::MEMBER],
        ]))
        ->assertSessionHasNoErrors();

    expect($target->fresh()->hasRole(RoleSeeder::MEMBER))->toBeTrue();
});

it('creates a user already holding a role', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('dash.users.store'), [
            'name' => 'Новый',
            'email' => 'novyi@example.kz',
            'password' => 'dovolno-dlinnyi-parol-777',
            'locale' => 'ru',
            'timezone' => 'Asia/Almaty',
            'roles_submitted' => '1',
            'roles' => [RoleSeeder::MEMBER],
        ])
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'novyi@example.kz')->first()->hasRole(RoleSeeder::MEMBER))
        ->toBeTrue();
});

it('shows which roles each user holds', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.users.index'))
        ->assertInertia(fn ($page) => $page
            ->has('roles')
            ->where('canManageRoles', true)
            ->has('users.data.0.roles'),
        );
});

it('takes a role away', function () {
    $target = User::factory()->create();
    $target->syncRoles([RoleSeeder::MEMBER]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('dash.users.update', $target), form($target, [
            'roles_submitted' => '1',
            'roles' => [],
        ]));

    expect($target->fresh()->roles)->toBeEmpty();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('refuses to let an admin take the role off themselves', function () {
    // A one-way door: the panel closes on the next request and the only
    // way back is the command line.
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('dash.users.update', $admin), form($admin, [
            'roles_submitted' => '1',
            'roles' => [],
        ]));

    expect($admin->fresh()->hasRole(RoleSeeder::ADMIN))->toBeTrue();
});

it('rejects a role that does not exist', function () {
    // A name invented through the form grants nothing while looking like
    // it granted something.
    $target = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('dash.users.update', $target), form($target, [
            'roles_submitted' => '1',
            'roles' => ['nachalnik-vsego'],
        ]))
        ->assertSessionHasErrors('roles.0');
});

it('ignores roles sent by someone who may not manage them', function () {
    // The field is hidden for them, so anything arriving in it was put
    // there by hand.
    $editor = User::factory()->create();
    $editor->syncRoles([RoleSeeder::MEMBER]);
    $editor->givePermissionTo(Ability::VIEW_USERS, Ability::MANAGE_USERS);

    $target = User::factory()->create();

    $this->actingAs($editor)
        ->put(route('dash.users.update', $target), form($target, [
            'roles_submitted' => '1',
            'roles' => [RoleSeeder::ADMIN],
        ]));

    expect($target->fresh()->hasRole(RoleSeeder::ADMIN))->toBeFalse();
});

it('hides the field from someone who may not manage roles', function () {
    $editor = User::factory()->create();
    $editor->givePermissionTo(Ability::VIEW_USERS);

    $this->actingAs($editor)
        ->get(route('dash.users.index'))
        ->assertInertia(fn ($page) => $page->where('canManageRoles', false));
});

// ── Empty data ───────────────────────────────────────────────────────

it('accepts a form with no roles field at all', function () {
    // Editing a name must not silently strip somebody's access.
    $target = User::factory()->create();
    $target->syncRoles([RoleSeeder::MEMBER]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('dash.users.update', $target), form($target, ['name' => 'Другое имя']))
        ->assertSessionHasNoErrors();

    expect($target->fresh()->hasRole(RoleSeeder::MEMBER))->toBeTrue()
        ->and($target->fresh()->name)->toBe('Другое имя');
});

it('seeds no people at all', function () {
    // The seeder shipped a "Test User" with a known address and no role.
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
