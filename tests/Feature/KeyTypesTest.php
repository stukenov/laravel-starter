<?php

use Illuminate\Support\Facades\DB;

/**
 * RULE(db): identifiers are UUIDv7 — AGENTS.md.
 *
 * The rule is easy to state and easy to break by accident, because the
 * framework's own helpers default to integers. `$table->morphs('subject')`
 * writes a `bigint` and says nothing; so does `foreignId()`. Both look
 * right in a migration and fail at the first insert, with an error about
 * a type mismatch rather than about the rule that was broken.
 *
 * This already happened twice — spatie's permission tables and its
 * activity log both shipped bigint morph keys and had to be rewritten.
 * The lesson stayed in a comment on those two migrations, where the third
 * occurrence would not have looked.
 *
 * Tables the application does not own are exempt: their keys were decided
 * elsewhere, which rule 3 now says out loud. They are listed here by
 * name, so adding one is a decision somebody makes on purpose.
 */

/** Tables whose keys belong to somebody else — see rule 3. */
const FOREIGN_TABLES = [
    // spatie/laravel-schedule-monitor: ops bookkeeping, nothing links to
    // it, no URL carries one of its ids.
    'monitored_scheduled_tasks',
    'monitored_scheduled_task_log_items',
    // laravel/framework: its own bookkeeping, same reasoning.
    'jobs',
    'job_batches',
    'failed_jobs',
    'cache',
    'cache_locks',
    'health_check_result_history_items',
    // Laravel's own: the migration ledger and the session store. Their
    // shape is the framework's business, not ours.
    'migrations',
    'sessions',
    'password_reset_tokens',
];

// ── Correct data ─────────────────────────────────────────────────────

it('gives every table it owns a uuid primary key', function () {
    $integerKeys = collect(DB::select("
        select c.table_name, c.data_type
        from information_schema.columns c
        join information_schema.table_constraints tc
          on tc.table_name = c.table_name and tc.constraint_type = 'PRIMARY KEY'
        join information_schema.key_column_usage k
          on k.constraint_name = tc.constraint_name and k.column_name = c.column_name
        where c.table_schema = 'public' and c.column_name = 'id'
    "))
        ->reject(fn ($row) => in_array($row->table_name, FOREIGN_TABLES, true))
        ->reject(fn ($row) => $row->data_type === 'uuid')
        ->map(fn ($row) => "{$row->table_name} ({$row->data_type})");

    expect($integerKeys->all())->toBe([]);
});

it('gives every relation column a uuid too', function () {
    // `morphs()` and `foreignId()` both write bigint by default. A morph
    // column pointing at a uuid table is a foreign key that can never
    // match anything.
    $mismatched = collect(DB::select("
        select table_name, column_name, data_type
        from information_schema.columns
        where table_schema = 'public'
          and column_name like '%\\_id'
          and data_type <> 'uuid'
    "))
        ->reject(fn ($row) => in_array($row->table_name, FOREIGN_TABLES, true))
        ->map(fn ($row) => "{$row->table_name}.{$row->column_name} ({$row->data_type})");

    expect($mismatched->all())->toBe([]);
});

// ── Wrong data ───────────────────────────────────────────────────────

it('names the exempt tables rather than skipping silently', function () {
    // An exemption list that drifts out of date exempts things nobody
    // decided to exempt.
    $existing = collect(DB::select("
        select table_name from information_schema.tables
        where table_schema = 'public'
    "))->pluck('table_name');

    // toContain()'s second argument is another needle, not a message —
    // the trap this project has a card about, walked into again. The
    // offenders are collected by hand instead.
    $missing = array_values(array_filter(
        FOREIGN_TABLES,
        fn (string $table) => ! $existing->contains($table),
    ));

    expect($missing)->toBe([]);
});

// ── Empty data ───────────────────────────────────────────────────────

it('finds tables to check at all', function () {
    // A query that matches nothing passes every assertion above.
    $tables = DB::select("
        select table_name from information_schema.tables where table_schema = 'public'
    ");

    expect(count($tables))->toBeGreaterThan(10);
});
