<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docs', function (Blueprint $table) {
            // RULE(db): uuid key, value produced by the database — AGENTS.md
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('attachment')->nullable();
            $table->timestampsTz();
        });

        DB::statement('alter table docs alter column id set default uuidv7()');

        // Substring search over the first field, matching the users slice.
        DB::statement('create index docs_title_trgm_idx on docs using gin (title gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('docs');
    }
};
