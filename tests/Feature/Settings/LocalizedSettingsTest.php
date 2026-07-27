<?php

use App\Models\User;

function signedIn(string $locale = 'ru'): User
{
    return User::factory()->create(['locale' => $locale]);
}

// Правильные данные
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

// Неправильные данные
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
    // citext делает почту регистронезависимой — проверка должна это учитывать,
    // иначе на уникальном индексе вылетит исключение вместо понятной ошибки.
    User::factory()->create(['email' => 'zanyato@example.kz']);

    $this->actingAs(signedIn())
        ->patch(route('profile.update'), ['name' => 'Тест', 'email' => 'Zanyato@Example.KZ'])
        ->assertSessionHasErrors('email');
});

// Пустые данные
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
    // Пункт меню отдаётся ключом, а переводит лэйаут. Пропущенный ключ
    // выведет на экран строку вида «nav.profile» — тест ловит это заранее.
    $translations = require lang_path('ru/common.php');

    foreach (['profile', 'security', 'locale', 'sessions', 'dashboard'] as $key) {
        expect($translations['nav'][$key] ?? null)->not->toBeNull($key);
    }
});
