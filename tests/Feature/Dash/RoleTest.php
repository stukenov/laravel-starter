<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Access\Ability;
use Database\Seeders\RoleSeeder;

// ── Correct data ─────────────────────────────────────────────────────

it('shows the roles list', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.roles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dash/roles/index')
            ->has('roles', 2),
        );
});

it('counts permissions and users per role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dash.roles.index'))
        ->assertInertia(fn ($page) => $page
            ->where('roles.0.permissions_count', count(Ability::all()))
            ->where('roles.0.users_count', 1),
        );
});

it('opens the permission matrix on its own page', function () {
    // A large form gets a page, not a dialog. See AGENTS.md.
    $role = Role::where('name', RoleSeeder::MEMBER)->first();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.roles.edit', $role))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dash/roles/edit')
            ->has('abilities', count(Ability::all()))
            ->has('granted', 0),
        );
});

it('creates a role', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('dash.roles.store'), [
            'name' => 'editor',
            'permissions' => [Ability::VIEW_USERS],
        ])
        ->assertSessionHasNoErrors();

    $role = Role::where('name', 'editor')->first();

    expect($role)->not->toBeNull()
        ->and($role->permissions->pluck('name')->all())->toBe([Ability::VIEW_USERS]);
});

it('syncs permissions on update', function () {
    $role = Role::where('name', RoleSeeder::MEMBER)->first();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('dash.roles.update', $role), [
            'name' => $role->name,
            'permissions' => [Ability::VIEW_USERS, Ability::VIEW_ROLES],
        ])
        ->assertSessionHasNoErrors();

    expect($role->fresh()->permissions)->toHaveCount(2);
});

it('deletes an ordinary role', function () {
    $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('dash.roles.destroy', $role))
        ->assertRedirect(route('dash.roles.index'));

    expect(Role::find($role->id))->toBeNull();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('refuses to delete the admin role', function () {
    // Nobody would be left able to grant it back, and the panel locks
    // everyone out for good.
    $role = Role::where('name', RoleSeeder::ADMIN)->first();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('dash.roles.destroy', $role))
        ->assertForbidden();

    expect(Role::find($role->id))->not->toBeNull();
});

it('rejects a permission that no code checks', function () {
    // A name invented through the form enforces nothing, so the list is
    // closed rather than free-form.
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('dash.roles.store'), [
            'name' => 'editor',
            'permissions' => ['invoices.approve'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

it('rejects a duplicate role name', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('dash.roles.store'), ['name' => RoleSeeder::ADMIN])
        ->assertSessionHasErrors('name');
});

it('rejects a name with spaces or capitals', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('dash.roles.store'), ['name' => 'Super Admin'])
        ->assertSessionHasErrors('name');
});

it('refuses a user without the roles permission', function () {
    $plain = User::factory()->create();
    $role = Role::where('name', RoleSeeder::MEMBER)->first();

    $this->actingAs($plain)->get(route('dash.roles.index'))->assertForbidden();
    $this->actingAs($plain)->get(route('dash.roles.edit', $role))->assertForbidden();
    $this->actingAs($plain)->post(route('dash.roles.store'), ['name' => 'x'])->assertForbidden();
});

it('refuses an anonymous request', function () {
    $this->get(route('dash.roles.index'))->assertRedirect(route('login'));
});

// ── Empty data ───────────────────────────────────────────────────────

it('accepts a role with no permissions at all', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('dash.roles.store'), ['name' => 'observer'])
        ->assertSessionHasNoErrors();

    expect(Role::where('name', 'observer')->first()->permissions)->toBeEmpty();
});

it('strips every permission when the list is emptied', function () {
    $role = Role::where('name', RoleSeeder::ADMIN)->first();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('dash.roles.update', $role), ['name' => $role->name]);

    expect($role->fresh()->permissions)->toBeEmpty();
});

it('rejects an entirely empty form', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('dash.roles.store'), [])
        ->assertSessionHasErrors('name');
});
