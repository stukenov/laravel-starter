<?php

use App\Models\Activity;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\Tenancy;
use Database\Seeders\RoleSeeder;

/**
 * RULE(api): the same controllers, the same policies — AGENTS.md.
 *
 * The point of sharing the actions is that these tests and the panel's
 * tests cannot disagree about who may do what: there is one policy
 * object and one form request behind both. What is left to check is
 * everything the sharing does not give away for free — that a token is
 * really required, that the envelope is what a caller can rely on, and
 * that tenancy reaches this surface too.
 *
 * That last one is the reason this file exists at all. `SetTenant` was
 * written for the web group, and an API request never passes through it:
 * without the middleware on the api group as well, a token would read
 * every organisation in the database while the panel showed one.
 */
function tokenFor(User $user, string $name = 'test'): string
{
    return $user->createToken($name)->plainTextToken;
}

function apiGet(string $uri, ?string $token = null)
{
    return test()->withHeaders(array_filter([
        'Accept' => 'application/json',
        'Authorization' => $token ? "Bearer {$token}" : null,
    ]))->getJson($uri);
}

// ── Correct data ─────────────────────────────────────────────────────

it('answers a valid token with the caller', function () {
    $user = User::factory()->create();

    apiGet('/api/v1/me', tokenFor($user))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('returns a list in the same envelope as every other', function () {
    // `data` at the top, always. A caller that has to guess the shape per
    // endpoint writes a client per endpoint.
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    apiGet('/api/v1/users', tokenFor($admin))
        ->assertOk()
        ->assertJsonStructure(['data' => ['data', 'links', 'current_page']]);
});

it('writes through the same form request the panel uses', function () {
    $admin = User::factory()->admin()->create();

    $this->withHeader('Authorization', 'Bearer '.tokenFor($admin))
        ->postJson('/api/v1/users', [
            'name' => 'Через API',
            'email' => 'api@example.com',
            'password' => 'a-long-enough-password',
            'locale' => 'ru',
            'timezone' => 'Asia/Almaty',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'api@example.com');

    expect(User::where('email', 'api@example.com')->exists())->toBeTrue();
});

it('answers 204 with no body when a record is deleted', function () {
    $admin = User::factory()->admin()->create();
    $victim = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.tokenFor($admin))
        ->deleteJson("/api/v1/users/{$victim->id}")
        ->assertNoContent();

    expect(User::find($victim->id))->toBeNull();
});

it('scopes a token to its own tenant', function () {
    // The reason this file exists. Without SetTenant on the api group a
    // token reads every organisation while the panel shows one.
    config(['tenancy.enabled' => true]);
    Tenancy::forget();

    $tenant = Tenant::create(['name' => 'One', 'slug' => 'one']);
    $other = Tenant::create(['name' => 'Two', 'slug' => 'two']);

    $admin = Tenancy::as($tenant->id, fn () => User::factory()->admin()->create());
    $stranger = Tenancy::as($other->id, fn () => User::factory()->create());

    Tenancy::forget();

    $ids = apiGet('/api/v1/users', tokenFor($admin))
        ->assertOk()
        ->json('data.data');

    expect(collect($ids)->pluck('id'))->not->toContain($stranger->id);

    Tenancy::forget();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('refuses a request with no token', function () {
    // 401, not a redirect to the sign-in page. A client following
    // redirects would otherwise get an HTML login form and a 200.
    apiGet('/api/v1/users')->assertUnauthorized();
});

it('refuses a token that was revoked', function () {
    $user = User::factory()->admin()->create();
    $token = tokenFor($user);

    $user->tokens()->delete();

    apiGet('/api/v1/me', $token)->assertUnauthorized();
});

it('applies the same policy as the panel', function () {
    // Not a separate check of the rule — literally the same policy
    // object. What is checked is that the API goes through it at all.
    $member = User::factory()->create();
    $member->assignRole(RoleSeeder::MEMBER);

    apiGet('/api/v1/users', tokenFor($member))->assertForbidden();
});

it('rejects a bad payload with the panel’s own messages', function () {
    $admin = User::factory()->admin()->create();

    $this->withHeader('Authorization', 'Bearer '.tokenFor($admin))
        ->postJson('/api/v1/users', ['name' => '', 'email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email']);
});

it('will not let one person revoke another person’s token', function () {
    $mine = User::factory()->create();
    $theirs = User::factory()->create();

    $victim = $theirs->createToken('theirs')->accessToken;

    $this->withHeader('Authorization', 'Bearer '.tokenFor($mine))
        ->deleteJson("/api/v1/tokens/{$victim->id}")
        ->assertNotFound();

    expect($theirs->tokens()->count())->toBe(1);
});

// ── Empty data ───────────────────────────────────────────────────────

it('returns an empty list rather than an error', function () {
    // Creating the caller writes an audit entry, so the log is cleared
    // afterwards — an "empty" case that quietly holds one row is not one.
    $admin = User::factory()->admin()->create();
    $token = tokenFor($admin);

    Activity::query()->delete();

    apiGet('/api/v1/activity', $token)
        ->assertOk()
        ->assertJsonPath('data.data', []);
});

it('lists no tokens for somebody who has none', function () {
    $user = User::factory()->create();

    // Creating one to authenticate with is unavoidable, so the check is
    // that the list holds exactly it and nothing invented alongside.
    apiGet('/api/v1/tokens', tokenFor($user))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
