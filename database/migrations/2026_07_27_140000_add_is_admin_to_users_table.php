<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A stopgap until spatie/laravel-permission lands in stage 3.
     *
     * Without it UserPolicy had to allow everything to every signed-in
     * user, and registration is open by default — so anyone could sign
     * up and then change any other account's email and password. Roles
     * "coming later" is not a reason to ship an open door now.
     *
     * When real roles arrive this column goes away in the same migration
     * that introduces them.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });

        // Partial index: administrators are a handful among many users,
        // and every policy check filters on this column.
        DB::statement('create index users_is_admin_idx on users (is_admin) where is_admin');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
