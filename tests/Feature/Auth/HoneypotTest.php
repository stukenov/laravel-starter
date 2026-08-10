<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Spatie\Honeypot\EncryptedTime;

/**
 * RULE(external): no third-party domain at render time — AGENTS.md.
 *
 * A captcha would need one, and the browser would block it, so this is
 * the whole of the protection on the forms a stranger can reach.
 *
 * Written against the password-reset request rather than registration:
 * registration is a documented switch and most projects close it, and a
 * suite that only tested the form that disappears left the honeypot
 * untested on exactly those deployments. The registration case is still
 * here, guarded by the feature.
 */
function resetRequest(array $overrides = []): array
{
    return array_merge(['email' => 'zabyl@example.kz'], $overrides);
}

function personWhoForgot(): User
{
    return User::factory()->create(['email' => 'zabyl@example.kz']);
}
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
    Notification::fake();
    $user = personWhoForgot();

    $this->post(route('password.email'), honeypot() + resetRequest())
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('lets a person register through it too', function () {
    $this->post(route('register.store'), honeypot() + registration())
        ->assertRedirect(route('dash.home', absolute: false));

    expect(User::where('email', 'novyi@example.kz')->exists())->toBeTrue();
})->skip(
    fn () => ! Features::enabled(Features::registration()),
    'registration is closed: REGISTRATION_ENABLED=false unregisters the route',
);

it('offers the fields on the form', function () {
    // Rendered by the page, not a blade component: the form is React.
    // Without these props the page has nothing to submit and every real
    // registration would be rejected.
    $this->get(route('password.request'))
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

    Notification::fake();
    $user = personWhoForgot();

    $this->post(route('password.email'), $fields + resetRequest());

    Notification::assertSentTo($user, ResetPassword::class);
});

// ── Wrong data ───────────────────────────────────────────────────────

it('rejects a filled honeypot', function () {
    // Nobody can see the field, so anything in it came from a machine.
    $filled = honeypot();
    $filled[config('honeypot.name_field_name')] = 'бот написал сюда';

    Notification::fake();
    personWhoForgot();

    $this->post(route('password.email'), $filled + resetRequest());

    Notification::assertNothingSent();
});

it('rejects a form submitted faster than a person could type', function () {
    $tooFast = honeypot();
    $tooFast[config('honeypot.valid_from_field_name')] = EncryptedTime::create(now()->addMinute());

    Notification::fake();
    personWhoForgot();

    $this->post(route('password.email'), $tooFast + resetRequest());

    Notification::assertNothingSent();
});

it('rejects a forged timestamp', function () {
    // The value is encrypted, so a made-up one cannot be decrypted and
    // must not be treated as "no timestamp given".
    $forged = honeypot();
    $forged[config('honeypot.valid_from_field_name')] = 'ничего не значащая строка';

    Notification::fake();
    personWhoForgot();

    $this->post(route('password.email'), $forged + resetRequest());

    Notification::assertNothingSent();
});

it('answers without an Inertia modal', function () {
    // A blank 200 — spatie's default — makes the client draw its error
    // modal, and this project shows pages instead. See AGENTS.md.
    $filled = honeypot();
    $filled[config('honeypot.name_field_name')] = 'бот';

    personWhoForgot();

    $this->from(route('password.request'))
        ->post(route('password.email'), $filled + resetRequest())
        ->assertRedirect(route('password.request'));
});

// ── Empty data ───────────────────────────────────────────────────────

it('rejects a submission with no honeypot fields at all', function () {
    // The common case: a bot posting the fields it can see and nothing
    // else. Left lenient, this is the one that gets through.
    Notification::fake();
    personWhoForgot();

    $this->post(route('password.email'), resetRequest());

    Notification::assertNothingSent();
});

it('rejects an empty timestamp', function () {
    $empty = honeypot();
    $empty[config('honeypot.valid_from_field_name')] = '';

    Notification::fake();
    personWhoForgot();

    $this->post(route('password.email'), $empty + resetRequest());

    Notification::assertNothingSent();
});

it('guards nothing but the public write forms', function () {
    // The middleware is on named routes only. Applied to the whole web
    // group it would demand the fields from every form in the panel.
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.users.index'))
        ->assertOk();
});
