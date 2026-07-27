<?php

use App\Models\User;

// ── Correct data ─────────────────────────────────────────────────────

it('sends a signed-in person from the login page to the panel', function () {
    // Laravel's default is `/`, which here redirects on to the public home
    // page. Following an old bookmark then looks like being signed out.
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(config('fortify.home'));
});

it('does the same on the registration page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('register'))
        ->assertRedirect(config('fortify.home'));
});

it('points at the panel and not at the public root', function () {
    expect(config('fortify.home'))->toBe('/dash');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('leaves the login page alone for a stranger', function () {
    $this->get(route('login'))->assertOk();
});

it('still sends an anonymous request from the panel to the login page', function () {
    $this->get(route('dash.users.index'))->assertRedirect(route('login'));
});

// ── Empty data ───────────────────────────────────────────────────────

it('needs no session to reach a public page', function () {
    $this->assertGuest();

    $this->get(route('home', ['locale' => 'ru']))->assertOk();
});
