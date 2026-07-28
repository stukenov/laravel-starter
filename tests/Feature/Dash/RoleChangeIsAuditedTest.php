<?php

use App\Models\Activity;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * The activity log exists to answer "who gave this person access".
 *
 * That was the one question it could not answer. `logOnly` watches
 * columns, and a role is not a column — it is a row in a pivot table. So
 * granting somebody administrator wrote nothing at all: the entry count
 * before and after a pure role change was identical, verified against a
 * real request before this was fixed.
 *
 * Everything else in the panel was logged, which made the gap worse: the
 * log looked complete, and the one action worth auditing was the one it
 * had never recorded.
 */
function grantRoles(User $actor, User $target, array $roles): void
{
    test()->actingAs($actor)->put(route('dash.users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'locale' => $target->locale,
        'timezone' => $target->timezone,
        'roles_submitted' => 1,
        'roles' => $roles,
    ]);
}

// ── Correct data ─────────────────────────────────────────────────────

it('records who granted a role, to whom, and from what', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $member->assignRole(RoleSeeder::MEMBER);

    grantRoles($admin, $member, [RoleSeeder::ADMIN]);

    $entry = Activity::where('event', 'roles')->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->causer_id)->toBe($admin->id)
        ->and($entry->subject_id)->toBe($member->id)
        ->and($entry->attribute_changes['old']['roles'])->toBe([RoleSeeder::MEMBER])
        ->and($entry->attribute_changes['attributes']['roles'])->toBe([RoleSeeder::ADMIN]);
});

it('puts the change where the panel reads it', function () {
    // `withChanges` and `withProperties` write to different columns, and
    // the page reads `attribute_changes`. Sent to the wrong one the entry
    // shows up with nothing in it — worse than no entry, because it looks
    // like a change that had no content.
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    grantRoles($admin, $member, [RoleSeeder::ADMIN]);

    $entry = Activity::where('event', 'roles')->latest('id')->first();

    expect($entry->attribute_changes)->not->toBeEmpty();
});

it('has a label for the event in every language', function () {
    // Without one the page prints `activity.events.roles` at the reader.
    foreach (['ru', 'kk', 'en'] as $locale) {
        $events = require lang_path("{$locale}/entities/activity.php");

        expect($events['events'])->toHaveKey('roles');
    }
});

// ── Wrong data ───────────────────────────────────────────────────────

it('records nothing when the roles did not change', function () {
    // An audit log that fires on every save teaches people to ignore it.
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $member->assignRole(RoleSeeder::MEMBER);

    grantRoles($admin, $member, [RoleSeeder::MEMBER]);

    expect(Activity::where('event', 'roles')->count())->toBe(0);
});

it('records nothing when the actor may not manage roles', function () {
    // The controller drops the roles for somebody without the
    // permission; the log must not claim a change that did not happen.
    $plain = User::factory()->create();
    $plain->assignRole(RoleSeeder::MEMBER);
    $member = User::factory()->create();

    grantRoles($plain, $member, [RoleSeeder::ADMIN]);

    expect(Activity::where('event', 'roles')->count())->toBe(0)
        ->and($member->fresh()->hasRole(RoleSeeder::ADMIN))->toBeFalse();
});

// ── Empty data ───────────────────────────────────────────────────────

it('records the removal of every role', function () {
    // Taking access away is as much an audit event as granting it, and
    // an empty list is easy to treat as "nothing to record".
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $member->assignRole(RoleSeeder::MEMBER);

    grantRoles($admin, $member, []);

    $entry = Activity::where('event', 'roles')->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->attribute_changes['attributes']['roles'])->toBe([]);
});

it('records a grant made from the command line', function () {
    // `php artisan user:admin` is the most privileged grant there is and
    // it used to leave no trace. No causer, because whoever ran it holds
    // a shell rather than a session — the page shows that as "system",
    // which is honest.
    $user = User::factory()->create();

    Artisan::call('user:admin', ['email' => $user->email]);

    $entry = Activity::where('event', 'roles')->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->causer_id)->toBeNull()
        ->and($entry->subject_id)->toBe($user->id)
        ->and($entry->attribute_changes['attributes']['roles'])->toBe([RoleSeeder::ADMIN]);
});
