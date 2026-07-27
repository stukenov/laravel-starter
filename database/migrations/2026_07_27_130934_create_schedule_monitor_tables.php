<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RULE(db): every timestamp column is timestamptz — AGENTS.md.
 *
 * Spatie ships this migration with `dateTime()` and `timestamps()`, which
 * on PostgreSQL means "timestamp without time zone". The whole point of
 * the schedule monitor is answering "did the nightly job run", and a
 * timestamp with no offset gives a different answer once the server moves
 * or the session zone changes. Rewritten with the Tz variants.
 *
 * The bigint keys are left alone. Nothing links to these rows, no URL
 * carries one, and rewriting an ops table to uuid would buy nothing.
 *
 * `jsonb` rather than `json` for the same reason as the activity log:
 * json keeps the raw text and cannot take a GIN index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_scheduled_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name');
            $table->string('type')->nullable();
            $table->string('cron_expression');
            $table->string('timezone')->nullable();
            $table->string('ping_url')->nullable();

            $table->dateTimeTz('last_started_at')->nullable();
            $table->dateTimeTz('last_finished_at')->nullable();
            $table->dateTimeTz('last_failed_at')->nullable();
            $table->dateTimeTz('last_skipped_at')->nullable();

            $table->dateTimeTz('registered_on_oh_dear_at')->nullable();
            $table->dateTimeTz('last_pinged_at')->nullable();
            $table->integer('grace_time_in_minutes');

            $table->timestampsTz();
        });

        Schema::create('monitored_scheduled_task_log_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('monitored_scheduled_task_id');
            $table
                ->foreign('monitored_scheduled_task_id', 'fk_scheduled_task_id')
                ->references('id')
                ->on('monitored_scheduled_tasks')
                ->cascadeOnDelete();

            $table->string('type');

            $table->jsonb('meta')->nullable();

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_scheduled_task_log_items');
        Schema::dropIfExists('monitored_scheduled_tasks');
    }
};
