<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Correct data
it('runs the application in UTC', function () {
    expect(config('app.timezone'))->toBe('UTC');
});

it('runs the database connection in UTC too', function () {
    // Without this PHP writes a naive time string and Postgres reads
    // it in its session timezone — the instant shifts by the offset.
    // It only shows up where time is compared: this is what produced
    // expired password reset tokens for us.
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
    // refresh() is required: the default is set by the database, and
    // the in-memory model doesn't know about it until it re-reads
    // the row.
    expect(User::factory()->create()->refresh()->timezone)->toBe('Asia/Almaty')
        ->and(User::factory()->create()->refresh()->locale)->toBe('ru');
});

it('stores every timestamp in the database with a timezone', function () {
    // A timestamp without one silently loses the offset when the database
    // moves to a server in another zone.
    //
    // Every table, not just users: a package migration is exactly how a
    // zoneless column gets in — spatie/laravel-schedule-monitor shipped
    // ten of them, and only a check this wide would have said so.
    $zoneless = collect(DB::select("
        select table_name, column_name from information_schema.columns
        where table_schema = 'public'
          and data_type like 'timestamp%'
          and data_type <> 'timestamp with time zone'
        order by table_name, column_name
    "))->map(fn ($column) => "{$column->table_name}.{$column->column_name}");

    expect($zoneless->all())->toBe([]);
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

// Wrong data
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

// Empty data
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
