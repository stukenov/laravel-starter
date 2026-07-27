<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ПРАВИЛО(база): расширения ставятся первой миграцией — AGENTS.md.
     *
     * citext нужен для регистронезависимой почты, pg_trgm — для поиска
     * по подстроке, btree_gin — чтобы класть в один индекс jsonb вместе
     * с обычными колонками.
     *
     * uuidv7() здесь нет намеренно: в PostgreSQL 18 это встроенная функция,
     * расширение не требуется.
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
