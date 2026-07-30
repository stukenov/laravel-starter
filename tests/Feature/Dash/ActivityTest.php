<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ── Correct data ─────────────────────────────────────────────────────

it('shows the activity log', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.activity.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dash/activity/index'));
});

it('records what actually changed on a user', function () {
    $user = User::factory()->create(['name' => 'Было']);
    Activity::query()->delete();

    $user->update(['name' => 'Стало']);

    $entry = Activity::latest()->first();

    expect($entry->event)->toBe('updated')
        ->and($entry->log_name)->toBe('user')
        ->and($entry->attribute_changes['old']['name'])->toBe('Было')
        ->and($entry->attribute_changes['attributes']['name'])->toBe('Стало');
});

it('flattens changes into one row per field', function () {
    // The raw shape is two parallel objects, which is unreadable and makes
    // every consumer zip them itself.
    $user = User::factory()->create(['name' => 'Было', 'locale' => 'ru']);
    Activity::query()->delete();

    $user->update(['name' => 'Стало', 'locale' => 'kk']);

    $changes = collect(Activity::latest()->first()->toFeedItem()['changes']);

    expect($changes)->toHaveCount(2)
        ->and($changes->firstWhere('field', 'locale'))
        ->toBe(['field' => 'locale', 'from' => 'ru', 'to' => 'kk']);
});

it('names the causer when there is one', function () {
    $actor = User::factory()->admin()->create(['name' => 'Сакен']);
    $target = User::factory()->create();
    Activity::query()->delete();

    $this->actingAs($actor)->put(route('dash.users.update', $target), [
        'name' => 'Новое имя',
        'email' => $target->email,
        'password' => '',
        'locale' => 'ru',
        'timezone' => 'Asia/Almaty',
    ]);

    expect(Activity::latest()->first()->toFeedItem()['causer']['name'])->toBe('Сакен');
});

it('filters by log name and event', function () {
    $user = User::factory()->create();
    $user->update(['name' => 'Изменено']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.activity.index', ['log' => 'user', 'event' => 'updated']))
        ->assertInertia(fn ($page) => $page->has('entries.data', 1));
});

it('stores the payload as jsonb with a gin index', function () {
    // json on PostgreSQL keeps the raw text and cannot take a GIN index,
    // so every filter over properties would become a full scan. Choosing
    // jsonb was the whole point.
    $type = DB::selectOne("
        select udt_name from information_schema.columns
        where table_name = 'activity_log' and column_name = 'properties'
    ")->udt_name;

    $indexes = collect(DB::select("select indexdef from pg_indexes where tablename = 'activity_log'"))
        ->pluck('indexdef')
        ->implode(' ');

    expect($type)->toBe('jsonb')
        ->and($indexes)->toContain('gin');
});

it('searches inside the payload', function () {
    activity()->withProperties(['order' => ['id' => 'A-100']])->log('оплата');

    $found = DB::selectOne(
        'select id from activity_log where properties @> ?::jsonb',
        [json_encode(['order' => ['id' => 'A-100']])],
    );

    expect($found)->not->toBeNull();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('never records the password', function () {
    // A hash is useless in a log and dangerous in a backup of one.
    $user = User::factory()->create();
    Activity::query()->delete();

    $user->update(['password' => 'a-completely-new-password']);

    expect(json_encode(Activity::latest()->first()?->attribute_changes ?? []))
        ->not->toContain('password');
});

it('records nothing when nothing changed', function () {
    $user = User::factory()->create(['name' => 'Имя']);
    Activity::query()->delete();

    $user->update(['name' => 'Имя']);

    expect(Activity::count())->toBe(0);
});

it('refuses a user without the activity permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dash.activity.index'))
        ->assertForbidden();
});

it('refuses an anonymous request', function () {
    $this->get(route('dash.activity.index'))->assertRedirect(route('login'));
});

it('offers no way to write to the log', function () {
    // An audit log that can be edited is not an audit log.
    $writable = collect(Route::getRoutes())
        ->filter(fn ($route) => str_contains($route->uri(), 'activity'))
        ->reject(fn ($route) => $route->methods() === ['GET', 'HEAD']);

    expect($writable)->toBeEmpty();
});

// ── Empty data ───────────────────────────────────────────────────────

it('shows an empty state with no entries', function () {
    // The admin is created first: creating a user is itself a logged
    // event, so clearing the table before that leaves one entry behind.
    $admin = User::factory()->admin()->create();

    Activity::query()->delete();

    $this->actingAs($admin)
        ->get(route('dash.activity.index'))
        ->assertInertia(fn ($page) => $page->has('entries.data', 0));
});

it('returns nothing for a filter that matches nothing', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.activity.index', ['log' => 'nothing-like-this']))
        ->assertInertia(fn ($page) => $page->has('entries.data', 0));
});

it('survives an entry with no causer and no changes', function () {
    Activity::query()->delete();
    activity()->log('системное событие');

    $item = Activity::latest()->first()->toFeedItem();

    expect($item['causer'])->toBeNull()
        ->and($item['changes'])->toBeEmpty()
        ->and($item['created_at'])->not->toBeNull();
});
