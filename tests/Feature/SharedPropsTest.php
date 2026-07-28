<?php

use App\Models\User;

/**
 * RULE(conventions): a model says what may leave it — AGENTS.md.
 *
 * The shared props handed the page `$request->user()` — the model itself
 * — on every Inertia response for every signed-in person. The hottest
 * path in the application, and the one place in this codebase that used a
 * list of what to withhold rather than a list of what to send.
 *
 * Nothing leaked, because `#[Hidden]` covers the password and the
 * remember token. That is the whole problem: a withhold-list fails
 * silently and in the dangerous direction. Add a column to `users` — an
 * internal note, a flag, an integration token — and it appears in the
 * JSON of every panel page until somebody remembers to extend the list.
 * A send-list fails by omitting a field, which is noticed at once.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('sends exactly the fields the front end reads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dash.home'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.id', $user->id)
            ->where('auth.user.name', $user->name)
            ->where('auth.user.email', $user->email)
            ->has('auth.user.locale')
            ->has('auth.user.timezone')
            ->has('auth.user.email_verified_at'),
        );
});

it('sends nothing else', function () {
    // The assertion that matters, and it compares the key set rather than
    // asking `hasAll(...)->etc(false)` — which passed with the whole
    // model in place, so it was checking nothing. Caught by falsification.
    //
    // What the whole model was actually sending: `created_at`,
    // `updated_at`, and — because the same middleware calls
    // `getAllPermissions()` for the flat ability list — the entire
    // `roles` and `permissions` relations as objects, on every response,
    // beside the flat list that already said the same thing.
    $user = User::factory()->admin()->create();
    $keys = [];

    $this->actingAs($user)
        ->get(route('dash.home'))
        ->assertInertia(function ($page) use (&$keys) {
            $keys = array_keys($page->toArray()['props']['auth']['user'] ?? []);

            return $page;
        });

    sort($keys);

    expect($keys)->toBe([
        'avatar', 'email', 'email_verified_at', 'id', 'locale', 'name', 'timezone',
    ]);
});

// ── Wrong data ───────────────────────────────────────────────────────

it('never sends the password or the remember token', function () {
    // Belt as well as braces: this is what `#[Hidden]` was carrying alone.
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dash.home'))
        ->assertInertia(fn ($page) => $page
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token'),
        );
});

// ── Empty data ───────────────────────────────────────────────────────

it('sends null for a visitor with no session', function () {
    // Public pages carry `auth.user = null`, and a page that reads it
    // without checking breaks for exactly the visitors it was built for.
    $this->get('/ru')
        ->assertInertia(fn ($page) => $page->where('auth.user', null));
});
