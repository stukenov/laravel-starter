<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RULE(db): uuid keys, timestamptz dates, jsonb for the payload.
 *
 * The published migration uses bigint ids, nullableMorphs with an
 * unsignedBigInteger key — which cannot point at our uuid users — and
 * `json` rather than `jsonb`. On PostgreSQL `json` stores the raw text and
 * cannot be indexed by GIN, so every filter over properties becomes a full
 * scan. The whole point of choosing jsonb was to avoid that.
 *
 * See AGENTS.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('log_name')->nullable()->index();
            $table->text('description');

            $table->string('subject_type')->nullable();
            $table->uuid('subject_id')->nullable();

            $table->string('event')->nullable();

            $table->string('causer_type')->nullable();
            $table->uuid('causer_id')->nullable();

            $table->jsonb('attribute_changes')->nullable();
            $table->jsonb('properties')->nullable();

            $table->timestampsTz();

            $table->index(['subject_type', 'subject_id'], 'activity_log_subject_index');
            $table->index(['causer_type', 'causer_id'], 'activity_log_causer_index');
        });

        DB::statement('alter table activity_log alter column id set default uuidv7()');

        // The reason jsonb exists here: searching inside the payload without
        // a full scan. btree_gin lets log_name ride along in the same index,
        // which is how the log screen actually filters.
        DB::statement('create index activity_log_properties_idx on activity_log using gin (properties jsonb_path_ops)');
        DB::statement('create index activity_log_name_created_idx on activity_log using gin (log_name, created_at)');

        // The feed is always read newest-first, and that is the one query
        // that must stay fast as the table grows.
        DB::statement('create index activity_log_created_at_idx on activity_log (created_at desc)');
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
