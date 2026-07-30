<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RULE(db): identifiers are UUIDv7 and timestamps are timestamptz — AGENTS.md.
 *
 * Sanctum ships this migration with `$table->id()` and
 * `$table->morphs('tokenable')`, which is a bigint key and a bigint
 * pointer at a uuid table — a foreign key that can never match anything.
 * It is the third package to do this here, after spatie's permissions and
 * its activity log, and `KeyTypesTest` exists because of the first two.
 *
 * The timestamps are `timestamptz` for the same reason as everywhere
 * else: `last_used_at` and `expires_at` are moments in time, and a column
 * without a zone shifts silently when the session zone changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampsTz();
        });

        DB::statement('alter table personal_access_tokens alter column id set default uuidv7()');
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
