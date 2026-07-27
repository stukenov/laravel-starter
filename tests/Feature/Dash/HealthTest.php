<?php

use App\Models\User;
use App\Support\Access\Ability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Enums\Status;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

/**
 * Writes a result the way the scheduled command would.
 *
 * The page never runs a check itself — a disk or database check firing on
 * every page load would make the panel slow and, on a hung check, hang it.
 */
function storeResult(string $name, Status $status, string $summary = ''): void
{
    HealthCheckResultHistoryItem::create([
        'check_name' => $name,
        'check_label' => $name,
        'status' => $status->value,
        'notification_message' => $summary,
        'short_summary' => $summary,
        'meta' => [],
        'ended_at' => now(),
        'batch' => '019fa2f8-0000-7000-8000-000000000001',
    ]);
}

// ── Correct data ─────────────────────────────────────────────────────

it('shows the checks', function () {
    storeResult('Database', Status::ok(), 'Ok');
    storeResult('Cache', Status::ok(), 'Ok');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.health.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dash/health/index')
            ->has('checks', 2)
            ->where('healthy', true),
        );
});

it('sends the time as ISO 8601 and not preformatted', function () {
    // RULE(time): the server never formats a date — AGENTS.md.
    storeResult('Database', Status::ok(), 'Ok');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.health.index'))
        ->assertInertia(fn ($page) => $page
            ->where('finished_at', fn ($value) => str_contains((string) $value, 'T')),
        );
});

it('stores health timestamps with a timezone', function () {
    // Spatie's stub ships `timestamp()`, which on PostgreSQL has no
    // offset. Rewritten in the migration; this is what holds it there.
    $type = DB::selectOne("
        select data_type from information_schema.columns
        where table_name = 'health_check_result_history_items' and column_name = 'ended_at'
    ")->data_type;

    expect($type)->toBe('timestamp with time zone');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('calls the whole thing unhealthy when one check failed', function () {
    storeResult('Database', Status::ok(), 'Ok');
    storeResult('Queue', Status::failed(), 'Queue jobs are not running');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.health.index'))
        ->assertInertia(fn ($page) => $page->where('healthy', false));
});

it('refuses a user without the health permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dash.health.index'))
        ->assertForbidden();
});

it('refuses an anonymous request', function () {
    $this->get(route('dash.health.index'))->assertRedirect(route('login'));
});

it('keeps the json endpoint unregistered without a secret', function () {
    // The fallback of an open endpoint would be a list of which parts of
    // the system are currently down, handed to anybody who asks.
    expect(config('health.json_secret'))->toBeNull()
        ->and(Route::has('health.json'))->toBeFalse();

    $this->get('/health.json')->assertNotFound();
});

// ── Empty data ───────────────────────────────────────────────────────

it('does not call an empty result set healthy', function () {
    // No results means the schedule has never run, which is exactly the
    // thing worth knowing — not "everything is fine".
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dash.health.index'))
        ->assertInertia(fn ($page) => $page
            ->has('checks', 0)
            ->where('healthy', false)
            ->where('finished_at', null),
        );
});

it('grants the permission to the admin role', function () {
    expect(User::factory()->admin()->create()->can(Ability::VIEW_HEALTH))->toBeTrue();
});
