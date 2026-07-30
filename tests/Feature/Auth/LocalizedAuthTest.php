<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Arr;

// Correct data
it('serves every auth screen', function (string $route) {
    $this->get($route)->assertOk();
})->with([
    fn () => route('login'),
    fn () => route('register'),
    fn () => route('password.request'),
]);

it('passes the dictionary to auth screens', function () {
    $this->get(route('login'))->assertInertia(fn ($page) => $page
        ->has('translations')
        ->where('locale', fn (string $locale) => in_array($locale, ['ru', 'kk', 'en'], true)),
    );
});

it('shows the auth screens in the locale from the profile', function () {
    $user = User::factory()->create(['locale' => 'kk']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->where('locale', 'kk'));
});

// Wrong data
it('has no hardcoded english left on auth screens', function () {
    // A missing translation doesn't break the build or fail a test —
    // it just stays English in the middle of a Russian interface.
    // Caught here with an explicit list.
    $phrases = [
        'Log in to your account',
        'Enter your email',
        'Forgot your password',
        'Create account',
        'Already have an account',
        'Confirm password',
        'Email address',
        'Remember me',
        'Full name',
    ];

    $offenders = [];

    foreach (glob(resource_path('js/pages/auth/*.tsx')) as $file) {
        $contents = file_get_contents($file);

        foreach ($phrases as $phrase) {
            if (str_contains($contents, '"'.$phrase) || str_contains($contents, '>'.$phrase.'<')) {
                $offenders[] = basename($file).': '.$phrase;
            }
        }
    }

    expect($offenders)->toBeEmpty();
});

it('declares layout titles by key, not by text', function () {
    // The static layout property can't call a hook, so it holds a
    // key instead. Text left there directly would never get translated.
    foreach (glob(resource_path('js/pages/auth/*.tsx')) as $file) {
        $contents = file_get_contents($file);

        if (str_contains($contents, '.layout = {')) {
            expect($contents)->toContain('titleKey:')
                ->and($contents)->not->toContain("    title: '");
        }
    }
});

it('validates in the active locale', function () {
    $user = User::factory()->create(['locale' => 'ru']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => '', 'email' => 'битая'])
        ->assertSessionHasErrors(['name', 'email']);
});

// Empty data
it('keeps the auth section in sync across locales', function () {
    $ru = Arr::dot(require lang_path('ru/common.php'));
    $kk = Arr::dot(require lang_path('kk/common.php'));
    $en = Arr::dot(require lang_path('en/common.php'));

    $authKeys = fn (array $all) => array_values(array_filter(
        array_keys($all),
        fn (string $key) => str_starts_with($key, 'auth.'),
    ));

    expect($authKeys($kk))->toBe($authKeys($ru))
        ->and($authKeys($en))->toBe($authKeys($ru))
        ->and($authKeys($ru))->not->toBeEmpty();
});

it('leaves no empty translation values', function () {
    // An empty string looks like "translation missing" but the key
    // exists — a key-set comparison wouldn't catch this.
    foreach (['ru', 'kk', 'en'] as $locale) {
        foreach (Arr::dot(require lang_path("{$locale}/common.php")) as $key => $value) {
            expect(trim((string) $value))->not->toBe('', "{$locale}: {$key}");
        }
    }
});

it('renders a screen even when no user is signed in', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.user', null));
});
