<?php

use App\Models\User;

function signedIn(string $locale = 'ru'): User
{
    return User::factory()->create(['locale' => $locale]);
}

// Correct data
it('opens every settings screen', function (string $route) {
    $user = signedIn();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route))
        ->assertOk();
})->with(['profile.edit', 'security.edit', 'locale.edit', 'sessions.index']);

it('serves settings in the profile locale', function () {
    $this->actingAs(signedIn('kk'))
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->where('locale', 'kk'));
});

it('saves the profile', function () {
    $user = signedIn();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Дәурен', 'email' => 'dauren@example.kz'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->name)->toBe('Дәурен');
});

// Wrong data
it('rejects a broken email', function () {
    $this->actingAs(signedIn())
        ->patch(route('profile.update'), ['name' => 'Тест', 'email' => 'не-почта'])
        ->assertSessionHasErrors('email');
});

it('rejects an email already taken by somebody else', function () {
    User::factory()->create(['email' => 'zanyato@example.kz']);

    $this->actingAs(signedIn())
        ->patch(route('profile.update'), ['name' => 'Тест', 'email' => 'zanyato@example.kz'])
        ->assertSessionHasErrors('email');
});

it('rejects an email that differs only in case', function () {
    // citext makes email case-insensitive — validation must account
    // for that, or the unique index throws an exception instead of a
    // proper error message.
    User::factory()->create(['email' => 'zanyato@example.kz']);

    $this->actingAs(signedIn())
        ->patch(route('profile.update'), ['name' => 'Тест', 'email' => 'Zanyato@Example.KZ'])
        ->assertSessionHasErrors('email');
});

// Empty data
it('rejects an empty profile form', function () {
    $this->actingAs(signedIn())
        ->patch(route('profile.update'), ['name' => '', 'email' => ''])
        ->assertSessionHasErrors(['name', 'email']);
});

it('rejects an empty password form', function () {
    $this->actingAs(signedIn())
        ->put(route('user-password.update'), [])
        ->assertSessionHasErrors(['current_password', 'password']);
});

it('has no untranslated keys left in the settings menu', function () {
    // The menu item is passed as a key, and the layout translates it.
    // A missing key shows up on screen as literal text like
    // "nav.profile" — this test catches that ahead of time.
    $translations = require lang_path('ru/common.php');

    foreach (['profile', 'security', 'locale', 'sessions', 'dashboard'] as $key) {
        expect($translations['nav'][$key] ?? null)->not->toBeNull($key);
    }
});
