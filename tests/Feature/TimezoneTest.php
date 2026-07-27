<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Правильные данные
it('runs the application in UTC', function () {
    expect(config('app.timezone'))->toBe('UTC');
});

it('runs the database connection in UTC too', function () {
    // Без этого PHP пишет наивную строку времени, а Postgres трактует её
    // в зоне своей сессии — и момент уезжает на величину смещения.
    // Проявляется только там, где время сравнивается: у нас на этом
    // рождались просроченные токены сброса пароля.
    expect(DB::selectOne('show timezone')->TimeZone)->toBe('UTC');
});

it('round trips an instant through the database without shifting it', function () {
    $written = now();

    DB::table('password_reset_tokens')->insert([
        'email' => 'roundtrip@example.test',
        'token' => 'x',
        'created_at' => $written,
    ]);

    $read = DB::table('password_reset_tokens')
        ->where('email', 'roundtrip@example.test')
        ->value('created_at');

    expect(abs(now()->parse($read)->diffInSeconds($written)))->toBeLessThan(2);
});

it('defaults a new user to Asia/Almaty', function () {
    // refresh() обязателен: значение по умолчанию ставит база, а модель
    // в памяти о нём не знает, пока не перечитает запись.
    expect(User::factory()->create()->refresh()->timezone)->toBe('Asia/Almaty')
        ->and(User::factory()->create()->refresh()->locale)->toBe('ru');
});

it('stores timestamps with a timezone', function () {
    // timestamp без зоны молча теряет смещение при переносе базы
    // между серверами с разной локальной зоной.
    $columns = DB::select("
        select column_name, data_type from information_schema.columns
        where table_name = 'users' and column_name in ('created_at', 'updated_at', 'email_verified_at')
    ");

    foreach ($columns as $column) {
        expect($column->data_type)->toBe('timestamp with time zone', $column->column_name);
    }
});

it('lets a user change language and timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => 'Europe/Berlin', 'locale' => 'kk'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->timezone)->toBe('Europe/Berlin')
        ->and($user->locale)->toBe('kk');
});

it('shows the settings page with a list of timezones', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('locale.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/locale')
            ->has('timezones'),
        );
});

// Неправильные данные
it('rejects an unknown timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => 'Mars/Olympus', 'locale' => 'ru'])
        ->assertSessionHasErrors('timezone');

    expect($user->refresh()->timezone)->toBe('Asia/Almaty');
});

it('rejects an unsupported locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => 'Asia/Almaty', 'locale' => 'de'])
        ->assertSessionHasErrors('locale');
});

it('rejects a timezone that is merely an offset string', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => '+05:00', 'locale' => 'ru'])
        ->assertSessionHasErrors('timezone');
});

it('refuses an anonymous request', function () {
    $this->patch(route('locale.update'), ['timezone' => 'Asia/Almaty', 'locale' => 'ru'])
        ->assertRedirect(route('login'));
});

// Пустые данные
it('rejects an empty timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => '', 'locale' => 'ru'])
        ->assertSessionHasErrors('timezone');
});

it('rejects a missing locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => 'Asia/Almaty'])
        ->assertSessionHasErrors('locale');
});

it('rejects an entirely empty payload', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), [])
        ->assertSessionHasErrors(['timezone', 'locale']);
});
