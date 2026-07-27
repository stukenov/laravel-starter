<?php

use App\Support\Locale\Locales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // RULE(db): uuid key, value produced by the database — AGENTS.md
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('password');
            $table->string('locale', 5)->default(Locales::FALLBACK);
            $table->string('timezone', 64)->default('Asia/Almaty');
            $table->rememberToken();
            $table->timestampsTz();
        });

        // The default lives in the database, not the model, so a row
        // inserted by a migration, a script or by hand in psql still gets
        // a proper identifier.
        DB::statement('alter table users alter column id set default uuidv7()');

        // citext makes email uniqueness case-insensitive without any
        // normalisation in code: Saken@Example.KZ and saken@example.kz
        // are the same address to the database.
        DB::statement('alter table users alter column email type citext');

        // Panel search over users: full text across name and email.
        DB::statement("
            alter table users add column search_vector tsvector
            generated always as (
                to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(email, ''))
            ) stored
        ");
        DB::statement('create index users_search_vector_idx on users using gin (search_vector)');

        // Complements full text: matches a fragment, not a whole word.
        DB::statement('create index users_name_trgm_idx on users using gin (name gin_trgm_ops)');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        DB::statement('alter table password_reset_tokens alter column email type citext');

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Must match users.id or the relation cannot be built.
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
