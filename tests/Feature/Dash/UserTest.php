<?php

use App\Models\User;

function admin(): User
{
    return User::factory()->admin()->create(['name' => 'Админ', 'email' => 'admin@example.kz']);
}

function payload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Дәурен Құрманов',
        'email' => 'dauren@example.kz',
        'password' => 'very-long-password-2026',
        'locale' => 'ru',
        'timezone' => 'Asia/Almaty',
    ], $overrides);
}

// ── Correct data ─────────────────────────────────────────────────────

it('shows the users list', function () {
    $user = admin();
    User::factory()->create(['name' => 'Айгүл Иманова']);

    $this->actingAs($user)
        ->get(route('dash.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dash/users/index')
            ->has('users.data', 2),
        );
});

it('finds a user by a full word', function () {
    $user = admin();
    User::factory()->create(['name' => 'Тукенов']);

    $this->actingAs($user)
        ->get(route('dash.users.index', ['search' => 'Тукенов']))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

it('finds a user by a short fragment', function () {
    // Полнотекстовый поиск ищет слова целиком: «Тук» не найдёт «Тукенов».
    // Короткие запросы уходят в pg_trgm — без этого поиск по мере набора
    // не работает вообще, а заметить это можно только руками.
    $user = admin();
    User::factory()->create(['name' => 'Тукенов']);

    $this->actingAs($user)
        ->get(route('dash.users.index', ['search' => 'Тук']))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

it('finds a user by email', function () {
    $user = admin();
    User::factory()->create(['email' => 'nurlan@example.kz']);

    $this->actingAs($user)
        ->get(route('dash.users.index', ['search' => 'nurlan']))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

it('creates a user', function () {
    $this->actingAs(admin())
        ->post(route('dash.users.store'), payload())
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'dauren@example.kz')->exists())->toBeTrue();
});

it('updates a user', function () {
    $target = User::factory()->create(['name' => 'Старое имя']);

    $this->actingAs(admin())
        ->put(route('dash.users.update', $target), payload([
            'name' => 'Новое имя',
            'email' => $target->email,
            'password' => '',
        ]))
        ->assertSessionHasNoErrors();

    expect($target->refresh()->name)->toBe('Новое имя');
});

it('keeps the old password when the field is left empty', function () {
    $target = User::factory()->create();
    $hash = $target->password;

    $this->actingAs(admin())->put(route('dash.users.update', $target), payload([
        'email' => $target->email,
        'password' => '',
    ]));

    expect($target->refresh()->password)->toBe($hash);
});

it('deletes a user', function () {
    $target = User::factory()->create();

    $this->actingAs(admin())
        ->delete(route('dash.users.destroy', $target))
        ->assertSessionHasNoErrors();

    expect(User::find($target->id))->toBeNull();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'zanyato@example.kz']);

    $this->actingAs(admin())
        ->post(route('dash.users.store'), payload(['email' => 'zanyato@example.kz']))
        ->assertSessionHasErrors('email');
});

it('rejects an email differing only in case', function () {
    User::factory()->create(['email' => 'zanyato@example.kz']);

    $this->actingAs(admin())
        ->post(route('dash.users.store'), payload(['email' => 'Zanyato@Example.KZ']))
        ->assertSessionHasErrors('email');
});

it('rejects an unsupported locale and timezone', function () {
    $this->actingAs(admin())
        ->post(route('dash.users.store'), payload(['locale' => 'de', 'timezone' => 'Mars/Olympus']))
        ->assertSessionHasErrors(['locale', 'timezone']);
});

it('refuses to let a user delete themselves', function () {
    // Иначе администратор одним кликом лишает систему последнего входа.
    $user = admin();

    $this->actingAs($user)
        ->delete(route('dash.users.destroy', $user))
        ->assertForbidden();

    expect(User::find($user->id))->not->toBeNull();
});

it('refuses a signed-in user who is not an administrator', function () {
    // Registration is open by default, so without this check anyone
    // could sign up and take over any account.
    $plain = User::factory()->create();

    $this->actingAs($plain)->get(route('dash.users.index'))->assertForbidden();
    $this->actingAs($plain)->post(route('dash.users.store'), payload())->assertForbidden();
    $this->actingAs($plain)
        ->put(route('dash.users.update', $plain), payload(['email' => $plain->email]))
        ->assertForbidden();
});

it('refuses a non administrator trying to delete somebody', function () {
    $plain = User::factory()->create();
    $victim = User::factory()->create();

    $this->actingAs($plain)
        ->delete(route('dash.users.destroy', $victim))
        ->assertForbidden();

    expect(User::find($victim->id))->not->toBeNull();
});

it('refuses an anonymous request', function () {
    $this->get(route('dash.users.index'))->assertRedirect(route('login'));
    $this->post(route('dash.users.store'), payload())->assertRedirect(route('login'));
});

// ── Empty data ───────────────────────────────────────────────────────

it('shows an empty list when there are no other users', function () {
    $this->actingAs(admin())
        ->get(route('dash.users.index'))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

it('returns nothing for a search with no matches', function () {
    $this->actingAs(admin())
        ->get(route('dash.users.index', ['search' => 'такого-точно-нет']))
        ->assertInertia(fn ($page) => $page->has('users.data', 0));
});

it('treats a blank search as no search at all', function () {
    $user = admin();
    User::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('dash.users.index', ['search' => '   ']))
        ->assertInertia(fn ($page) => $page->has('users.data', 3));
});

it('rejects an entirely empty form', function () {
    $this->actingAs(admin())
        ->post(route('dash.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password', 'locale', 'timezone']);
});
