<?php

use App\Models\User;
use App\Support\Access\Ability;
use Database\Seeders\RoleSeeder;

/**
 * RULE(access): the screen shows what the person may do — AGENTS.md.
 *
 * The panel offered every section to everybody and let the server say no:
 * a member saw Users, Roles, Activity and Health in the navigation and
 * got 403 from each. The permission was enforced — that is the half that
 * matters — but the interface was lying about what it could do.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('tells the page what the signed-in person may do', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.users.index'))
        ->assertInertia(fn ($page) => $page
            ->has('auth.abilities')
            ->where('auth.abilities', fn ($abilities) => collect($abilities)->contains(Ability::VIEW_USERS)),
        );
});

it('lists exactly what the role carries', function () {
    $member = User::factory()->create();
    $member->syncRoles([RoleSeeder::MEMBER]);
    $member->givePermissionTo(Ability::VIEW_ACTIVITY);

    $this->actingAs($member)
        ->get(route('dash.activity.index'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.abilities', [Ability::VIEW_ACTIVITY]),
        );
});

it('includes a permission granted through a role', function () {
    // Not only direct grants: the panel hands out roles, not permissions.
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.users.index'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.abilities', fn ($abilities) => collect($abilities)->count() === count(Ability::all())),
        );
});

// ── Wrong data ───────────────────────────────────────────────────────

it('does not hand out a permission the person lacks', function () {
    $member = User::factory()->create();
    $member->givePermissionTo(Ability::VIEW_ACTIVITY);

    $this->actingAs($member)
        ->get(route('dash.activity.index'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.abilities', fn ($abilities) => ! collect($abilities)->contains(Ability::MANAGE_USERS)),
        );
});

it('still refuses the request itself, not only the menu item', function () {
    // The list is for hiding a pointless click. It decides nothing: the
    // policy does, and it must keep doing so if somebody types the URL.
    $member = User::factory()->create();
    $member->givePermissionTo(Ability::VIEW_ACTIVITY);

    $this->actingAs($member)
        ->get(route('dash.users.index'))
        ->assertForbidden();
});

// ── Empty data ───────────────────────────────────────────────────────

it('sends an empty list for a person with no permissions', function () {
    $plain = User::factory()->create();

    $this->actingAs($plain)
        ->get(route('dash.home'))
        ->assertInertia(fn ($page) => $page->where('auth.abilities', []));
});

it('sends an empty list to a stranger rather than nothing at all', function () {
    // `undefined` on the client is a different shape from `[]`, and the
    // hook would have to handle both.
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('auth.abilities', []));
});
