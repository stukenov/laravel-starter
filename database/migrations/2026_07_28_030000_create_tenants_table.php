<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RULE(tenancy): one database, one set of migrations — AGENTS.md.
 *
 * The column is nullable and stays empty while `TENANCY_ENABLED` is
 * false, so a single-tenant project carries an unread column and nothing
 * else. Making it non-nullable later is a project's decision once it has
 * tenants; making it nullable now is what lets the feature be optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            // RULE(db): uuid key, value produced by the database — AGENTS.md
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestampsTz();
        });

        DB::statement('alter table tenants alter column id set default uuidv7()');

        // citext: a slug typed in capitals is the same slug, and this is
        // the comparison people get wrong with lower() indexes.
        DB::statement('alter table tenants alter column slug type citext');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('tenant_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
        });

        // Scoped reads always filter on this column, so it is always in
        // the where clause. Measured on nothing yet — the index is here
        // because every tenant query has it, not on a hunch about size.
        DB::statement('create index users_tenant_id_idx on users (tenant_id)');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::dropIfExists('tenants');
    }
};
