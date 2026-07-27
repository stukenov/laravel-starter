<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * RULE(db): extensions go in the first migration — AGENTS.md.
     *
     * citext gives case-insensitive email, pg_trgm powers substring
     * search, btree_gin lets a single index hold jsonb next to plain
     * columns.
     *
     * uuidv7() is deliberately absent: PostgreSQL 18 ships it as a
     * built-in function, no extension required.
     */
    public function up(): void
    {
        DB::statement('create extension if not exists citext');
        DB::statement('create extension if not exists pg_trgm');
        DB::statement('create extension if not exists btree_gin');
    }

    public function down(): void
    {
        DB::statement('drop extension if exists btree_gin');
        DB::statement('drop extension if exists pg_trgm');
        DB::statement('drop extension if exists citext');
    }
};
