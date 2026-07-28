<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Illuminate\Support\Str;

/**
 * RULE(tenancy): off by default, and off means nothing changes — AGENTS.md.
 *
 * The whole feature is a nullable column and a global scope. What makes
 * it worth testing rather than reading is that both failure modes are
 * silent: with the scope missing, one customer's list quietly includes
 * another's rows, and with the fill missing, a record is born owned by
 * nobody and disappears from every scoped query afterwards.
 *
 * The recursion trap has its own case. A scope that asked `auth()` for
 * the current tenant would query the users table to answer a question
 * asked by a query on the users table, and signing in would hang. The
 * middleware sets the tenant once and the scope only reads memory.
 */
beforeEach(function () {
    Tenancy::forget();
    config(['tenancy.enabled' => true]);
});

afterEach(fn () => Tenancy::forget());

function tenantWithUser(string $name): array
{
    $tenant = Tenant::create(['name' => $name, 'slug' => Str::slug($name)]);

    $user = Tenancy::as($tenant->id, fn () => User::factory()->create());

    return [$tenant, $user];
}

// ── Correct data ─────────────────────────────────────────────────────

it('shows a person only their own tenant', function () {
    [$first, $alice] = tenantWithUser('First');
    [, $bob] = tenantWithUser('Second');

    Tenancy::set($first->id);

    $visible = User::pluck('id');

    expect($visible)->toContain($alice->id)
        ->and($visible)->not->toContain($bob->id);
});

it('fills the tenant in when a record is created', function () {
    [$tenant] = tenantWithUser('Third');

    Tenancy::set($tenant->id);

    $created = User::factory()->create();

    expect($created->tenant_id)->toBe($tenant->id);
});

it('lets a person sign in while tenancy is on', function () {
    // The recursion trap. Resolving the user is itself a query on a
    // scoped model, so a scope that reached for `auth()` would never
    // finish. Nothing is set at that point, so the lookup is unscoped.
    [$tenant] = tenantWithUser('Fourth');

    $user = Tenancy::as($tenant->id, fn () => User::factory()->admin()->create([
        'password' => bcrypt('a-password-123'),
    ]));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'a-password-123',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user->fresh());
});

it('scopes what the panel lists to the signed-in tenant', function () {
    [$tenant] = tenantWithUser('Fifth');
    [, $stranger] = tenantWithUser('Sixth');

    $admin = Tenancy::as($tenant->id, fn () => User::factory()->admin()->create());

    Tenancy::forget();

    $this->actingAs($admin)
        ->get(route('dash.users.index'))
        ->assertInertia(fn ($page) => $page->has('users.data'));

    expect(Tenancy::current())->toBe($tenant->id)
        ->and(User::pluck('id')->all())->not->toContain($stranger->id);
});

// ── Wrong data ───────────────────────────────────────────────────────

it('changes nothing at all while the flag is off', function () {
    // The promise the option makes. With tenancy off both halves are
    // inert and the column stays empty.
    config(['tenancy.enabled' => false]);

    [$tenant] = tenantWithUser('Seventh');
    $second = User::factory()->create();

    Tenancy::set($tenant->id);

    expect(Tenancy::current())->toBeNull()
        ->and($second->tenant_id)->toBeNull()
        ->and(User::pluck('id'))->toContain($second->id);
});

it('shows an operator every tenant', function () {
    // Deliberate, written down in the config, and the reason a stray
    // account without a tenant is dangerous rather than merely odd.
    [, $alice] = tenantWithUser('Eighth');
    [, $bob] = tenantWithUser('Ninth');

    $operator = User::factory()->admin()->create(['tenant_id' => null]);

    $this->actingAs($operator)->get(route('dash.users.index'));

    expect(Tenancy::current())->toBeNull()
        ->and(User::pluck('id'))->toContain($alice->id)->toContain($bob->id);
});

it('puts the tenant back when work inside a tenant throws', function () {
    // A failed job otherwise rescopes every job the worker runs after it.
    [$tenant] = tenantWithUser('Tenth');

    Tenancy::set(null);

    try {
        Tenancy::as($tenant->id, fn () => throw new RuntimeException('boom'));
    } catch (RuntimeException) {
        // expected
    }

    expect(Tenancy::current())->toBeNull();
});

// ── Empty data ───────────────────────────────────────────────────────

it('scopes to nothing before the middleware has run', function () {
    // Signing in happens here, and it must see the whole table.
    Tenancy::forget();

    expect(Tenancy::current())->toBeNull()
        ->and(Tenancy::resolved())->toBeFalse();
});

it('reaches across tenants only where it says so', function () {
    [, $alice] = tenantWithUser('Eleventh');
    [$second, $bob] = tenantWithUser('Twelfth');

    Tenancy::set($second->id);

    expect(User::pluck('id'))->not->toContain($alice->id)
        ->and(User::query()->acrossTenants()->pluck('id'))
        ->toContain($alice->id)->toContain($bob->id);
});
