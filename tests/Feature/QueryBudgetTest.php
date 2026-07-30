<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * RULE(speed): measure, do not reason — AGENTS.md.
 *
 * An N+1 is invisible at development scale. Twenty rows cost twenty-one
 * queries against a warm local database and nobody notices; the same page
 * at two thousand rows costs two thousand and one, and the site is down
 * for reasons that appear in no error log.
 *
 * What is asserted is a ceiling, not an exact count. The first attempt
 * compared the count at two sizes and demanded they match, which failed
 * for a reason that had nothing to do with N+1: the activity page eager
 * loads the author of each entry, and Laravel skips that query entirely
 * when page one happens to hold no entry that has one. Whether it does is
 * a property of the fixture, not of the code.
 *
 * Measured across a 27-fold increase in rows — 61, 461 and 1661 users:
 *
 *     dash.users.index     4   4   4
 *     dash.roles.index     2   2   2
 *     dash.activity.index  2   3   3
 *
 * Flat, which is the whole point. Six leaves room for another join
 * without leaving room for a query per row.
 *
 * Each measurement is warmed first. The activity filter lists are cached
 * deliberately, and inserting rows invalidates them, so a count taken
 * straight after a write includes queries that repopulate a cache rather
 * than queries a real page load makes.
 */
const QUERY_BUDGET = 6;

function queriesFor(string $route, User $actor): int
{
    test()->actingAs($actor)->get(route($route));

    DB::flushQueryLog();
    DB::enableQueryLog();

    test()->actingAs($actor)->get(route($route));

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

// ── Correct data ─────────────────────────────────────────────────────

it('stays inside the query budget as the rows multiply', function (string $route) {
    $admin = User::factory()->admin()->create();

    User::factory()->count(60)->create();
    $small = queriesFor($route, $admin);

    User::factory()->count(600)->create();
    $large = queriesFor($route, $admin);

    expect($small)->toBeLessThanOrEqual(QUERY_BUDGET)
        ->and($large)->toBeLessThanOrEqual(QUERY_BUDGET);
})->with(['dash.users.index', 'dash.roles.index', 'dash.activity.index']);

// ── Wrong data ───────────────────────────────────────────────────────

it('would notice a query per row', function () {
    // The check itself, checked: loading a relation one row at a time is
    // exactly what this is for, so the counter has to see it happen.
    $admin = User::factory()->admin()->create();
    User::factory()->count(20)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();

    foreach (User::all() as $user) {
        $user->roles()->count();
    }

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeGreaterThan(QUERY_BUDGET);
});

// ── Empty data ───────────────────────────────────────────────────────

it('counts anything at all', function () {
    // Every assertion above passes if the log is always empty — which is
    // what happens when the query log is never enabled.
    $admin = User::factory()->admin()->create();

    expect(queriesFor('dash.users.index', $admin))->toBeGreaterThan(0);
});
