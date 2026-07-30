<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

/**
 * RULE(db): timestamptz everywhere, jsonb over json — AGENTS.md.
 *
 * Spatie's stub uses `timestamp()` and `json()`. Both are wrong here: a
 * check result whose time has no offset cannot be compared across servers,
 * and `json` on PostgreSQL keeps the raw text and refuses a GIN index.
 *
 * Bigint keys are kept. These rows are written every minute, read as a
 * batch and pruned after five days; nothing links to one and no URL
 * carries it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $item = new HealthCheckResultHistoryItem;

        $connection = $item->getConnectionName();
        $table = $item->getTable();

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();

            $table->string('check_name');
            $table->string('check_label');
            $table->string('status');
            $table->text('notification_message')->nullable();
            $table->string('short_summary')->nullable();
            $table->jsonb('meta');
            $table->timestampTz('ended_at');
            $table->uuid('batch');

            $table->timestampsTz();

            $table->index('created_at');
            $table->index('batch');
        });
    }

    public function down(): void
    {
        $item = new HealthCheckResultHistoryItem;

        Schema::connection($item->getConnectionName())->dropIfExists($item->getTable());
    }
};
