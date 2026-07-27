<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RULE(db): an index has to match the query, and the query has to be
 * measured — AGENTS.md.
 * Both halves of this migration came out of `explain analyze`
 * against 200 000 log rows and 50 000 users, not out of reasoning.
 *
 * **Added** `users (created_at desc)`. `UserController::index` sorts by
 * it on every visit and there was no index at all: the plan was a Sort
 * over the whole table. Now an Index Scan, 0.1 ms.
 *
 * **Dropped** `activity_log_name_created_idx`, which was GIN over
 * `(log_name, created_at)`. GIN answers "which rows match" and knows
 * nothing about order, so the planner never used it for the page that
 * filters and sorts — it went around it and sorted anyway. An index that
 * cost writes and disk and served nothing.
 *
 * **Not added:** btree composites on `(log_name, created_at)` and
 * `(event, created_at)`. They seemed obvious, were written, and then
 * measured: `pg_stat_user_indexes` showed `idx_scan = 0` for both. With
 * a handful of distinct log names the filter is not selective enough,
 * and the planner prefers the existing `created_at desc` index. Adding
 * them would have slowed every write to buy nothing.
 *
 * What the activity page actually costs is elsewhere — the two `distinct`
 * queries that fill the filter dropdowns scan the whole table on every
 * load. That is fixed by caching them, in the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create index if not exists users_created_at_idx on users (created_at desc)');

        DB::statement('drop index if exists activity_log_name_created_idx');
    }

    public function down(): void
    {
        DB::statement('drop index if exists users_created_at_idx');

        DB::statement('create index activity_log_name_created_idx on activity_log using gin (log_name, created_at)');
    }
};
