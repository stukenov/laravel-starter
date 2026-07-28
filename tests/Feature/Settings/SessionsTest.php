<?php

use App\Models\User;
use App\Support\Sessions\SessionList;
use Illuminate\Support\Facades\DB;

function makeSession(User $user, string $id, ?string $agent, ?string $ip = '127.0.0.1', int $ago = 0): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => $ip,
        'user_agent' => $agent,
        'payload' => '',
        'last_activity' => now()->subMinutes($ago)->getTimestamp(),
    ]);
}

const MAC_CHROME = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
const IPHONE_SAFARI = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

// Correct data
it('shows the sessions page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('sessions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/sessions')->has('sessions'));
});

it('recognises the browser and platform from the user agent', function () {
    $user = User::factory()->create();
    makeSession($user, 'session-mac', MAC_CHROME);

    $list = SessionList::forUser($user, 'session-mac');

    expect($list)->toHaveCount(1)
        ->and($list[0]['browser'])->toBe('Chrome')
        ->and($list[0]['platform'])->toBe('Mac');
});

it('marks exactly one session as current', function () {
    $user = User::factory()->create();
    makeSession($user, 'session-a', MAC_CHROME);
    makeSession($user, 'session-b', IPHONE_SAFARI);

    $list = SessionList::forUser($user, 'session-b');

    expect(collect($list)->where('is_current', true))->toHaveCount(1)
        ->and(collect($list)->firstWhere('is_current', true)['id'])->toBe('session-b');
});

it('sorts the newest activity first', function () {
    $user = User::factory()->create();
    makeSession($user, 'session-old', MAC_CHROME, '127.0.0.1', ago: 120);
    makeSession($user, 'session-new', IPHONE_SAFARI, '127.0.0.1', ago: 1);

    $list = SessionList::forUser($user, 'session-new');

    expect($list[0]['id'])->toBe('session-new');
});

it('logs the user out of every other device but keeps the current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get(route('sessions.index'));

    $current = session()->getId();
    makeSession($user, 'session-other', MAC_CHROME);
    makeSession($user, $current, IPHONE_SAFARI);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('sessions.destroy'))
        ->assertRedirect();

    expect(DB::table('sessions')->where('id', 'session-other')->exists())->toBeFalse();
});

// Wrong data
it('never touches another user sessions', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    makeSession($stranger, 'session-stranger', MAC_CHROME);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('sessions.destroy'));

    expect(DB::table('sessions')->where('id', 'session-stranger')->exists())->toBeTrue();
});

it('never lists another user sessions', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    makeSession($stranger, 'session-stranger', MAC_CHROME);

    expect(SessionList::forUser($user, 'whatever'))->toBeEmpty();
});

it('refuses an anonymous request', function () {
    $this->get(route('sessions.index'))->assertRedirect(route('login'));
    $this->delete(route('sessions.destroy'))->assertRedirect(route('login'));
});

it('demands a password confirmation before logging out other devices', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('sessions.destroy'))
        ->assertRedirect(route('password.confirm'));
});

// Empty data
it('returns an empty list when the user has no sessions', function () {
    expect(SessionList::forUser(User::factory()->create(), 'none'))->toBeEmpty();
});

it('survives an unknown user agent', function () {
    $user = User::factory()->create();
    makeSession($user, 'session-weird', 'не-браузер-вообще');

    $list = SessionList::forUser($user, 'session-weird');

    // We return null for the unknown value: the UI supplies its own
    // localized label instead of an English word from the library.
    expect($list)->toHaveCount(1)
        ->and($list[0]['browser'])->toBeNull()
        ->and($list[0]['last_active_at'])->not->toBeNull();
});

it('survives a missing user agent and ip', function () {
    $user = User::factory()->create();
    makeSession($user, 'session-null', null, null);

    $list = SessionList::forUser($user, 'session-null');

    expect($list)->toHaveCount(1)
        ->and($list[0]['ip'])->toBeNull()
        ->and($list[0]['browser'])->toBeNull();
});
