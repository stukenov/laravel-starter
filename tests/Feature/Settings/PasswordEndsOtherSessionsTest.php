<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * The commonest reason to change a password is the suspicion that
 * somebody else has the account.
 *
 * Changing it alone did nothing about that. A session is authenticated by
 * its own row, not by the password, so the intruder's session went on
 * working exactly as before and the person who had just "secured" their
 * account had done nothing of the kind. Nothing failed, nothing logged,
 * and the sessions page went on listing the stranger's device as though
 * that were expected.
 *
 * Confirmed before it was fixed: a session row planted for a second
 * device survived the password change intact.
 */
function plantSession(User $user, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => '203.0.113.9',
        'user_agent' => 'Somebody/1.0',
        'payload' => base64_encode(serialize(['_token' => 'x'])),
        'last_activity' => time(),
    ]);
}

/**
 * `user-password.update` is ours; `password.update` is Fortify's reset
 * flow, which is a different route with a similar name — writing the
 * wrong one here silently exercised nothing at all.
 */
function changePassword(User $user): void
{
    test()->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'old-password-123',
        'password' => 'a-brand-new-password-456',
        'password_confirmation' => 'a-brand-new-password-456',
    ]);
}

// ── Correct data ─────────────────────────────────────────────────────

it('ends every other session when the password changes', function () {
    $user = User::factory()->create(['password' => bcrypt('old-password-123')]);

    plantSession($user, 'someone-elses-device');
    plantSession($user, 'another-old-device');

    changePassword($user);

    expect(DB::table('sessions')->where('id', 'someone-elses-device')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'another-old-device')->exists())->toBeFalse();
});

it('actually changes the password too', function () {
    // The revocation must not become the whole of what this route does.
    $user = User::factory()->create(['password' => bcrypt('old-password-123')]);

    changePassword($user);

    expect(Hash::check('a-brand-new-password-456', $user->fresh()->password))->toBeTrue();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('leaves the sessions of other people alone', function () {
    // Scoped by user, or changing one password signs out the whole site.
    $user = User::factory()->create(['password' => bcrypt('old-password-123')]);
    $stranger = User::factory()->create();

    plantSession($stranger, 'unrelated-person');

    changePassword($user);

    expect(DB::table('sessions')->where('id', 'unrelated-person')->exists())->toBeTrue();
});

it('keeps nothing when the password was wrong', function () {
    // A failed attempt must not end anybody's session — otherwise a
    // stranger with a form and no password can sign you out at will.
    $user = User::factory()->create(['password' => bcrypt('old-password-123')]);

    plantSession($user, 'still-here');

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'not-the-password',
        'password' => 'a-brand-new-password-456',
        'password_confirmation' => 'a-brand-new-password-456',
    ]);

    expect(DB::table('sessions')->where('id', 'still-here')->exists())->toBeTrue();
});

// ── Empty data ───────────────────────────────────────────────────────

it('says only that the password changed when there was nothing else', function () {
    $user = User::factory()->create(['password' => bcrypt('old-password-123')]);

    changePassword($user);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())
        ->toBeLessThanOrEqual(1);
});

/*
 * The same defect, one flow further along and worse there.
 *
 * A reset is what somebody does when they have lost control of the
 * account — and it left every session running, including the one they
 * were trying to end. `password.update` is Fortify's reset route;
 * `user-password.update` above is ours. Two similar names for two
 * different things, which is how the first version of this file tested
 * nothing at all.
 */

it('ends every session when a forgotten password is reset', function () {
    $user = User::factory()->create();

    plantSession($user, 'intruder-from-before-the-reset');

    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'a-brand-new-password-456',
        'password_confirmation' => 'a-brand-new-password-456',
    ]);

    expect(DB::table('sessions')->where('id', 'intruder-from-before-the-reset')->exists())
        ->toBeFalse()
        ->and(Hash::check('a-brand-new-password-456', $user->fresh()->password))->toBeTrue();
});

it('leaves everybody else signed in during a reset', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    plantSession($stranger, 'unrelated-during-reset');

    $this->post(route('password.update'), [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'a-brand-new-password-456',
        'password_confirmation' => 'a-brand-new-password-456',
    ]);

    expect(DB::table('sessions')->where('id', 'unrelated-during-reset')->exists())->toBeTrue();
});

it('ends nothing when the reset token is wrong', function () {
    // Otherwise anybody who knows an email address can sign that person
    // out of everything, repeatedly, without ever having a valid token.
    $user = User::factory()->create();

    plantSession($user, 'survives-a-bad-token');

    $this->post(route('password.update'), [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'a-brand-new-password-456',
        'password_confirmation' => 'a-brand-new-password-456',
    ]);

    expect(DB::table('sessions')->where('id', 'survives-a-bad-token')->exists())->toBeTrue();
});
