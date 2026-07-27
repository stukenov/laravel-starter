<?php

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Support\Collection;

// Правильные данные
it('serves each supported locale', function (string $locale) {
    $this->get("/{$locale}")->assertOk();

    expect(app()->getLocale())->toBe($locale);
})->with(['ru', 'kk', 'en']);

it('shares translations with the front end', function () {
    $this->get('/ru')->assertInertia(fn ($page) => $page
        ->where('locale', 'ru')
        ->has('locales', 3)
        // Словарь плоский: ключ буквально 'auth.login'. Через where() его
        // не проверить — там точка означает вложенность, — поэтому
        // достаём весь словарь и смотрим ключ напрямую.
        // Inertia отдаёт сюда Collection, а не массив.
        ->where('translations', fn (Collection $t) => $t->get('auth.login') === 'Войти'),
    );
});

it('serves legal pages under every locale', function (string $locale) {
    $this->get("/{$locale}/privacy")->assertOk();
    $this->get("/{$locale}/terms")->assertOk();
})->with(['ru', 'kk', 'en']);

it('takes the locale from the profile inside the cabinet', function () {
    $user = User::factory()->create(['locale' => 'kk']);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect(app()->getLocale())->toBe('kk');
});

// Неправильные данные
it('returns 404 for an unsupported locale', function () {
    $this->get('/de')->assertNotFound();
});

it('returns 404 for a malformed locale', function () {
    $this->get('/RU-ru-ru')->assertNotFound();
});

it('ignores an unsupported locale stored on the user', function () {
    // Битое значение в профиле не выбирается, но и не роняет запрос:
    // источник просто пропускается, и язык берётся из следующего.
    $user = User::factory()->create(['locale' => 'de']);

    $this->actingAs($user)->get(route('dashboard'));

    expect(app()->getLocale())->toBeIn(SetLocale::SUPPORTED)
        ->and(app()->getLocale())->not->toBe('de');
});

// Пустые данные
it('redirects the bare root to a supported locale', function () {
    // Тестовый клиент Laravel по умолчанию шлёт Accept-Language en-us,
    // поэтому конкретный язык здесь зависит от клиента. Важно другое:
    // корень никогда не остаётся без локали.
    $response = $this->get('/');

    $response->assertRedirect();

    $target = ltrim(parse_url($response->headers->get('Location'), PHP_URL_PATH), '/');

    expect($target)->toBeIn(SetLocale::SUPPORTED);
});

it('falls back to ru when the browser sends no language', function () {
    $this->withHeaders(['Accept-Language' => ''])
        ->get('/')
        ->assertRedirect('/ru');
});

it('falls back to ru when the browser asks for a language we lack', function () {
    $this->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
        ->get('/')
        ->assertRedirect('/ru');
});

it('honours a browser language we do support', function () {
    $this->withHeaders(['Accept-Language' => 'kk-KZ,kk;q=0.9'])
        ->get('/')
        ->assertRedirect('/kk');
});

// Полнота переводов: пропущенный ключ выводится на экран как есть,
// поэтому расхождение между локалями надо ловить тестом, а не глазами.
it('keeps every locale file in sync', function () {
    $ru = Arr::dot(require lang_path('ru/common.php'));
    $kk = Arr::dot(require lang_path('kk/common.php'));
    $en = Arr::dot(require lang_path('en/common.php'));

    expect(array_keys($kk))->toBe(array_keys($ru))
        ->and(array_keys($en))->toBe(array_keys($ru));
});

// Кука локали: страницы вне префикса — вход и регистрация — иначе всегда
// открываются на запасном языке, каким бы ни был выбор пользователя.
it('remembers the locale chosen through the url', function () {
    $this->get('/kk')->assertCookie(SetLocale::COOKIE, 'kk', encrypted: false);
});

it('uses the remembered locale on pages outside the prefix', function () {
    $this->withUnencryptedCookie(SetLocale::COOKIE, 'kk')
        ->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('locale', 'kk'));
});

it('lets the profile win over the cookie', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->withUnencryptedCookie(SetLocale::COOKIE, 'kk')
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('ignores a broken cookie value', function () {
    $this->withUnencryptedCookie(SetLocale::COOKIE, 'de')
        ->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('locale', fn ($l) => in_array($l, ['ru', 'kk', 'en'], true)));
});
