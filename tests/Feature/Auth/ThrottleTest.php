<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * RULE(security): every public form that sends mail is limited — AGENTS.md.
 *
 * Fortify ships the password-reset routes with `guest` and nothing else.
 * Laravel's own `passwords.users.throttle` only stops a second token being
 * issued for one address within a minute; it does not stop the requests.
 * That is enough to fill somebody's inbox indefinitely.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('limits the password reset form', function () {
    expect(middlewareOf('password.email'))->toContain('throttle:password-reset');
});

it('limits the page that sets the new password too', function () {
    // Otherwise a stolen token can be brute-forced at full speed.
    expect(middlewareOf('password.update'))->toContain('throttle:password-reset');
});

it('still limits signing in', function () {
    expect(middlewareOf('login.store'))->toContain('throttle:login');
});

it('lets an honest request through', function () {
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('refuses the sixth attempt in a minute', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.email'), ['email' => $user->email]);
    }

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertStatus(429);
});

it('counts an unknown address the same way', function () {
    // Otherwise the form answers at full speed to made-up addresses, and
    // the mail queue is the thing that suffers.
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.email'), ['email' => 'nikogo@example.kz']);
    }

    $this->post(route('password.email'), ['email' => 'nikogo@example.kz'])
        ->assertStatus(429);
});

it('does not let one address lock out another', function () {
    $victim = User::factory()->create();

    for ($i = 0; $i < 6; $i++) {
        $this->post(route('password.email'), ['email' => 'shumnyi@example.kz']);
    }

    // The key is email plus address, so a noisy sender cannot shut the
    // door on somebody else who happens to share an IP.
    $this->post(route('password.email'), ['email' => $victim->email])
        ->assertStatus(302);
});

// ── Empty data ───────────────────────────────────────────────────────

it('limits an empty submission as well', function () {
    // A limiter that only counts well-formed requests is not a limiter.
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.email'), []);
    }

    $this->post(route('password.email'), [])->assertStatus(429);
});

it('leaves no public form that sends mail unlimited', function () {
    // The list is short on purpose: a new public route that sends mail
    // has to be added here consciously.
    $public = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array($route->getName(), [
            'login.store', 'register.store', 'password.email', 'password.update',
        ], true));

    foreach ($public as $route) {
        $guards = collect($route->gatherMiddleware())
            ->filter(fn ($m) => is_string($m)
                && (str_starts_with($m, 'throttle:') || str_contains($m, 'Honeypot')));

        expect($guards)->not->toBeEmpty($route->getName());
    }
});

function middlewareOf(string $name): array
{
    return Route::getRoutes()->getByName($name)?->gatherMiddleware() ?? [];
}
