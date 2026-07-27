<?php

use App\Models\Activity;
use App\Models\User;
use App\Support\Sse\PostgresListener;
use Illuminate\Support\Facades\DB;

/**
 * Writes through a connection of its own so the insert commits.
 *
 * NOTIFY is delivered at COMMIT, and the suite runs inside a transaction
 * that never commits — an insert made the ordinary way would never reach
 * a listener, and the test would fail for a reason that has nothing to do
 * with the trigger.
 */
function insertCommitted(string $description, string $blob = 'x'): void
{
    $config = config('database.connections.pgsql');

    // FORCE_NEW, or pg_connect() returns the listener's own connection and
    // the pg_close() below kills the wait this test is about to make.
    $connection = pg_connect(sprintf(
        'host=%s port=%s dbname=%s user=%s password=%s',
        $config['host'], $config['port'], $config['database'],
        $config['username'], $config['password'],
    ), PGSQL_CONNECT_FORCE_NEW);

    pg_query_params(
        $connection,
        'insert into activity_log (log_name, description, properties, created_at, updated_at)
         values ($1, $2, $3::jsonb, now(), now())',
        ['test', $description, json_encode(['blob' => $blob])],
    );

    pg_close($connection);
}

// ── Correct data ─────────────────────────────────────────────────────

it('serves the stream as an event stream', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.activity.stream'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/event-stream')
        ->assertHeader('cache-control', 'no-cache, no-transform')
        // Nginx buffers by default, which would hold every event until the
        // connection closes and defeat the whole point.
        ->assertHeader('x-accel-buffering', 'no');
})->skip(fn () => true, 'a streamed response blocks the test client for its full lifetime');

it('gives every entry a monotonic sequence', function () {
    // The uuid sorts by time, but a reconnecting browser sends
    // Last-Event-ID as a plain string — "greater than N" has to be a cheap
    // indexed comparison.
    activity()->log('первое');
    activity()->log('второе');

    $entries = Activity::orderBy('seq')->get();

    expect($entries->last()->seq)->toBeGreaterThan($entries->first()->seq);
});

it('returns only what came after the cursor', function () {
    activity()->log('до');
    $cursor = (int) Activity::latest('seq')->first()->seq;
    activity()->log('после');

    $fresh = Activity::since($cursor);

    expect($fresh)->toHaveCount(1)
        ->and($fresh->first()->description)->toBe('после');
});

it('starts a first connection at the tip, not at the beginning', function () {
    // The page arrives with the log already rendered. A first connection
    // that replayed from zero would show every one of those rows twice —
    // which is what it did, and it was visible only in a browser.
    activity()->log('уже на странице');

    expect(Activity::since(Activity::tip()))->toBeEmpty()
        ->and(Activity::count())->toBeGreaterThan(0);
});

it('catches up from the cursor a reconnect sends', function () {
    activity()->log('до обрыва');
    $cursor = Activity::tip();
    activity()->log('пока браузер переподключался');

    expect(Activity::since($cursor)->pluck('description')->all())
        ->toBe(['пока браузер переподключался']);
});

it('fires a notification on insert', function () {
    // NOTIFY is transactional: it is delivered at COMMIT. The test suite
    // runs inside an uncommitted transaction, so an insert made here would
    // never reach a listener. The write goes through a separate connection
    // that commits immediately — which is also what happens in production.
    $listener = new PostgresListener('activity_log');

    insertCommitted('уведомление из отдельного соединения');

    $payload = $listener->wait(2);
    $listener->close();

    expect($payload)->not->toBeNull()
        ->and($payload)->toMatch('/^\d+$/');
});

it('sends the sequence rather than the payload in the notification', function () {
    // Postgres caps a notification at 8000 bytes, and an entry with a large
    // jsonb payload would exceed it outright.
    $listener = new PostgresListener('activity_log');

    insertCommitted('крупная запись', str_repeat('x', 9000));

    $payload = $listener->wait(2);
    $listener->close();

    expect($payload)->toMatch('/^\d+$/')
        ->and(strlen((string) $payload))->toBeLessThan(20);
});

// ── Wrong data ───────────────────────────────────────────────────────

it('refuses a user without the activity permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dash.activity.stream'))
        ->assertForbidden();
});

it('refuses an anonymous request', function () {
    $this->get(route('dash.activity.stream'))->assertRedirect(route('login'));
});

it('treats a broken cursor as the beginning', function () {
    activity()->log('запись');

    expect(Activity::since(0))->not->toBeEmpty();
});

it('caps how much a single catch-up sends', function () {
    // A browser returning after a long absence must not be handed the
    // entire table in one burst.
    Activity::query()->delete();

    for ($i = 0; $i < 5; $i++) {
        activity()->log("запись {$i}");
    }

    expect(Activity::since(0)->count())->toBeLessThanOrEqual(200);
});

// ── Empty data ───────────────────────────────────────────────────────

it('returns nothing when the cursor is already current', function () {
    activity()->log('запись');
    $cursor = (int) Activity::latest('seq')->first()->seq;

    expect(Activity::since($cursor))->toBeEmpty();
});

it('returns nothing from an empty log', function () {
    Activity::query()->delete();

    expect(Activity::since(0))->toBeEmpty();
});

it('puts the tip at zero on an empty log', function () {
    // A first connection to an empty log must ask for "everything after 0",
    // not crash on a null max().
    Activity::query()->delete();

    expect(Activity::tip())->toBe(0);
});

it('times out cleanly with no notification', function () {
    // The null return is what tells the handler to send a heartbeat.
    $listener = new PostgresListener('activity_log');

    $payload = $listener->wait(0.3);
    $listener->close();

    expect($payload)->toBeNull();
});

it('keeps the sequence column indexed', function () {
    $indexes = collect(DB::select("select indexdef from pg_indexes where tablename = 'activity_log'"))
        ->pluck('indexdef')
        ->implode(' ');

    expect($indexes)->toContain('activity_log_seq_idx');
});

it('tells the browser how long to wait before reconnecting', function () {
    // The browser's own default is 2-3 seconds and varies between them.
    // Since a connection holds an FPM worker for its whole life, that
    // interval is the only lever the server has over what a viewer costs
    // — leaving it to the client is leaving the arithmetic to chance.
    expect(config('sse.retry_seconds'))->toBeGreaterThan(0)
        ->and(config('sse.lifetime_seconds'))->toBeGreaterThan(0);
});

it('costs less than one worker per viewer', function () {
    // lifetime / (lifetime + retry). At the defaults this is 0.89 — one
    // worker each in all but name, which is the number that decides how
    // many people can watch at once.
    $lifetime = (int) config('sse.lifetime_seconds');
    $retry = (int) config('sse.retry_seconds');

    expect($lifetime / ($lifetime + $retry))->toBeLessThan(1.0);
});

it('does not hold the connection past the pool timeout', function () {
    // README suggests request_terminate_timeout 40 for the stream pool.
    // A lifetime above it means FPM kills the worker mid-stream instead
    // of the loop ending cleanly, and the browser sees an error rather
    // than a close.
    expect((int) config('sse.lifetime_seconds'))->toBeLessThan(40);
});
