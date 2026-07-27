<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ПРАВИЛО(время): зона хранится у пользователя, приложение живёт в UTC.
     *
     * Значение по умолчанию — Asia/Almaty, потому что это зона заказчика.
     * На другом проекте меняется здесь одной строкой.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default(SetLocale::FALLBACK);
            $table->string('timezone', 64)->default('Asia/Almaty');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'timezone']);
        });
    }
};
