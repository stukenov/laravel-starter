<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Two things the profile page did to the account and then said nothing
 * about.
 *
 * Changing an address cleared `email_verified_at` and sent no mail. The
 * person was left unverified and uninformed: the new address got nothing,
 * and the first sign of trouble was `/dash` bouncing to a verify screen
 * the next time they opened it. Somebody who changed their address and
 * closed the tab got no notice at all.
 *
 * Deleting the account ended the current session and left every other one
 * in the table. Changing a password ends them — deleting the whole
 * account did not, which is backwards. `sessions.user_id` carries no
 * foreign key, so the database does not tidy up either: the rows sit
 * there, pointing at somebody who no longer exists, until the session
 * lifetime runs out.
 */
function otherDevice(User $user, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => '203.0.113.9',
        'user_agent' => 'Elsewhere/1.0',
        'payload' => base64_encode(serialize(['_token' => 'x'])),
        'last_activity' => time(),
    ]);
}

// ── Correct data ─────────────────────────────────────────────────────

it('sends a verification mail to the new address', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => 'somewhere-else@example.com',
    ]);

    Notification::assertSentTo($user->fresh(), VerifyEmail::class);

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('ends every other session when the account is deleted', function () {
    $user = User::factory()->create(['password' => bcrypt('a-password-123')]);

    otherDevice($user, 'left-behind-by-deletion');

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('profile.destroy'), ['password' => 'a-password-123']);

    expect(DB::table('sessions')->where('id', 'left-behind-by-deletion')->exists())->toBeFalse()
        ->and(User::find($user->id))->toBeNull();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('sends nothing when the address did not change', function () {
    // A verification mail on every profile save teaches people to ignore
    // verification mail.
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'A New Name',
        'email' => $user->email,
    ]);

    Notification::assertNothingSent();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('leaves other people signed in when one account goes', function () {
    $user = User::factory()->create(['password' => bcrypt('a-password-123')]);
    $stranger = User::factory()->create();

    otherDevice($stranger, 'nothing-to-do-with-it');

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('profile.destroy'), ['password' => 'a-password-123']);

    expect(DB::table('sessions')->where('id', 'nothing-to-do-with-it')->exists())->toBeTrue();
});

// ── Empty data ───────────────────────────────────────────────────────

it('deletes an account that was only ever signed in once', function () {
    // No other sessions to end is the ordinary case, and it must not be
    // the case that breaks.
    $user = User::factory()->create(['password' => bcrypt('a-password-123')]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('profile.destroy'), ['password' => 'a-password-123'])
        ->assertRedirect('/');

    expect(User::find($user->id))->toBeNull();
});

/*
 * RULE(files): the column holds a path, the disk holds the file — AGENTS.md.
 *
 * The starter's own use of the upload story, and the reason it exists
 * here rather than in an example entity: a feature with no consumer is a
 * feature nobody exercises. The picture is as private as the account —
 * it is served through the same download route as any other file, by the
 * account's own policy, never from a public disk.
 */

it('stores a photo and hands the page a route, not a path', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('снимок.jpg'),
    ]);

    $stored = $user->fresh();

    expect($stored->avatar)->toStartWith('files/users/')
        ->and(Storage::disk('local')->exists($stored->avatar))->toBeTrue()
        // What the front end receives is a URL it may fetch, never a
        // disk path it could not fetch anyway.
        ->and($stored->toSharedProps()['avatar'])->toContain('/dash/files/users/');
});

it('deletes the photo when the account goes', function () {
    Storage::fake('local');

    $user = User::factory()->create(['password' => bcrypt('a-password-123')]);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('снимок.jpg'),
    ]);

    $path = $user->fresh()->avatar;

    $this->actingAs($user->fresh())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('profile.destroy'), ['password' => 'a-password-123']);

    expect(Storage::disk('local')->exists($path))->toBeFalse();
});

it('refuses something that is not an image', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->create('вирус.exe', 10),
    ])->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar)->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('leaves the account without one when none was sent', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Новое имя',
        'email' => $user->email,
    ]);

    expect($user->fresh()->avatar)->toBeNull()
        ->and($user->fresh()->name)->toBe('Новое имя');
});
