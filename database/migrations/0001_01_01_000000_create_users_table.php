<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // ПРАВИЛО(база): ключ — uuid, значение выдаёт база — AGENTS.md
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('password');
            $table->string('locale', 5)->default(SetLocale::FALLBACK);
            $table->string('timezone', 64)->default('Asia/Almaty');
            $table->rememberToken();
            $table->timestampsTz();
        });

        // Значение по умолчанию ставится на уровне базы, а не в модели:
        // так идентификатор появится и у записи, вставленной мимо Eloquent —
        // миграцией, скриптом или руками в psql.
        DB::statement('alter table users alter column id set default uuidv7()');

        // citext делает уникальность почты регистронезависимой без
        // нормализации в коде: Saken@Example.KZ и saken@example.kz —
        // один и тот же адрес для базы.
        DB::statement('alter table users alter column email type citext');

        // Поиск по пользователям в панели: полнотекстовый по имени и почте.
        DB::statement("
            alter table users add column search_vector tsvector
            generated always as (
                to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(email, ''))
            ) stored
        ");
        DB::statement('create index users_search_vector_idx on users using gin (search_vector)');

        // Дополняет полнотекстовый поиск: ищет по куску слова, а не по слову.
        DB::statement('create index users_name_trgm_idx on users using gin (name gin_trgm_ops)');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        DB::statement('alter table password_reset_tokens alter column email type citext');

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Тип обязан совпадать с users.id, иначе связь не построится.
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
