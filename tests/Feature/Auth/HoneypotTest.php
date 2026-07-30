<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Honeypot\EncryptedTime;

/**
 * RULE(external): no third-party domain at render time — AGENTS.md.
 *
 * A captcha would need one, and the browser would block it, so this is
 * the whole of the protection on the only form a stranger can reach.
 */
function registration(array $overrides = []): array
{
    return array_merge([
        'name' => 'Новый пользователь',
        'email' => 'novyi@example.kz',
        'password' => 'a-strong-enough-password',
        'password_confirmation' => 'a-strong-enough-password',
    ], $overrides);
}

// ── Correct data ─────────────────────────────────────────────────────

it('lets a person through', function () {
    $this->post(route('register.store'), honeypot() + registration())
        ->assertRedirect(route('dash.home', absolute: false));

    expect(User::where('email', 'novyi@example.kz')->exists())->toBeTrue();
});

it('offers the fields on the form', function () {
    // Rendered by the page, not a blade component: the form is React.
    // Without these props the page has nothing to submit and every real
    // registration would be rejected.
    $this->get(route('register'))
        ->assertInertia(fn ($page) => $page
            ->has('honeypot.nameFieldName')
            ->has('honeypot.encryptedValidFrom')
            ->where('honeypot.enabled', true),
        );
});

it('accepts a randomized field name', function () {
    // The name carries a random suffix per render, so the server matches
    // it by prefix. Hard-coding the bare name would break the real form.
    $fields = [
        config('honeypot.name_field_name').'_abc123' => '',
        config('honeypot.valid_from_field_name') => EncryptedTime::create(now()->subMinute()),
    ];

    $this->post(route('register.store'), $fields + registration())
        ->assertRedirect(route('dash.home', absolute: false));
});

// ── Wrong data ───────────────────────────────────────────────────────

it('rejects a filled honeypot', function () {
    // Nobody can see the field, so anything in it came from a machine.
    $filled = honeypot();
    $filled[config('honeypot.name_field_name')] = 'бот написал сюда';

    $this->post(route('register.store'), $filled + registration());

    expect(User::where('email', 'novyi@example.kz')->exists())->toBeFalse();
    $this->assertGuest();
});

it('rejects a form submitted faster than a person could type', function () {
    $tooFast = honeypot();
    $tooFast[config('honeypot.valid_from_field_name')] = EncryptedTime::create(now()->addMinute());

    $this->post(route('register.store'), $tooFast + registration());

    expect(User::where('email', 'novyi@example.kz')->exists())->toBeFalse();
});

it('rejects a forged timestamp', function () {
    // The value is encrypted, so a made-up one cannot be decrypted and
    // must not be treated as "no timestamp given".
    $forged = honeypot();
    $forged[config('honeypot.valid_from_field_name')] = 'ничего не значащая строка';

    $this->post(route('register.store'), $forged + registration());

    expect(User::where('email', 'novyi@example.kz')->exists())->toBeFalse();
});

it('answers without an Inertia modal', function () {
    // A blank 200 — spatie's default — makes the client draw its error
    // modal, and this project shows pages instead. See AGENTS.md.
    $filled = honeypot();
    $filled[config('honeypot.name_field_name')] = 'бот';

    $this->from(route('register'))
        ->post(route('register.store'), $filled + registration())
        ->assertRedirect(route('register'));
});

// ── Empty data ───────────────────────────────────────────────────────

it('rejects a submission with no honeypot fields at all', function () {
    // The common case: a bot posting the fields it can see and nothing
    // else. Left lenient, this is the one that gets through.
    $this->post(route('register.store'), registration());

    expect(User::where('email', 'novyi@example.kz')->exists())->toBeFalse();
});

it('rejects an empty timestamp', function () {
    $empty = honeypot();
    $empty[config('honeypot.valid_from_field_name')] = '';

    $this->post(route('register.store'), $empty + registration());

    expect(User::where('email', 'novyi@example.kz')->exists())->toBeFalse();
});

it('guards nothing but registration', function () {
    // The middleware is on one route. Applied to the whole web group it
    // would demand the fields from every form in the panel.
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.users.index'))
        ->assertOk();
});
