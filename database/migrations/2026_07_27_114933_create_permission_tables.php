<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RULE(db): uuid keys everywhere, including the permission tables.
 *
 * The published spatie migration uses bigint ids and an unsignedBigInteger
 * morph key. That morph key points at users.id, which is a uuid here, so
 * the pivot would not bind at all. Rewritten rather than patched: mixing
 * key types in one schema only surfaces when a relation is finally used.
 *
 * See AGENTS.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        throw_if(
            Config::array('permission.table_names') === [],
            'config/permission.php not loaded. Run php artisan config:clear.',
        );

        // Read one key at a time rather than an array of them: a table
        // name is a string, and reaching into an untyped array for it
        // means `Schema::create()` is handed something nothing checked.
        $permissions = self::table('permissions');
        $roles = self::table('roles');
        $morphKey = Config::string('permission.column_names.model_morph_key');

        // Spatie ships these two as null, meaning "use the default", so
        // they cannot go through Config::string() — it refuses a null.
        $pivotRole = self::column('role_pivot_key', 'role_id');
        $pivotPermission = self::column('permission_pivot_key', 'permission_id');

        Schema::create($permissions, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($roles, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        DB::statement("alter table {$permissions} alter column id set default uuidv7()");
        DB::statement("alter table {$roles} alter column id set default uuidv7()");

        Schema::create(self::table('model_has_permissions'), function (Blueprint $table) use ($permissions, $morphKey, $pivotPermission) {
            $table->uuid($pivotPermission);
            $table->string('model_type');
            $table->uuid($morphKey);

            $table->index([$morphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')->on($permissions)
                ->cascadeOnDelete();

            $table->primary(
                [$pivotPermission, $morphKey, 'model_type'],
                'model_has_permissions_permission_model_type_primary',
            );
        });

        Schema::create(self::table('model_has_roles'), function (Blueprint $table) use ($roles, $morphKey, $pivotRole) {
            $table->uuid($pivotRole);
            $table->string('model_type');
            $table->uuid($morphKey);

            $table->index([$morphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')->on($roles)
                ->cascadeOnDelete();

            $table->primary(
                [$pivotRole, $morphKey, 'model_type'],
                'model_has_roles_role_model_type_primary',
            );
        });

        Schema::create(self::table('role_has_permissions'), function (Blueprint $table) use ($permissions, $roles, $pivotRole, $pivotPermission) {
            $table->uuid($pivotPermission);
            $table->uuid($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')->on($permissions)
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id')->on($roles)
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        $store = Config::string('permission.cache.store', 'default');

        app('cache')
            ->store($store === 'default' ? null : $store)
            ->forget(Config::string('permission.cache.key'));
    }

    public function down(): void
    {
        Schema::dropIfExists(self::table('role_has_permissions'));
        Schema::dropIfExists(self::table('model_has_roles'));
        Schema::dropIfExists(self::table('model_has_permissions'));
        Schema::dropIfExists(self::table('roles'));
        Schema::dropIfExists(self::table('permissions'));
    }

    /**
     * One table name from spatie's config, as a string.
     */
    private static function table(string $name): string
    {
        return Config::string("permission.table_names.{$name}");
    }

    /**
     * One column name, with spatie's own default for the nulls it ships.
     */
    private static function column(string $name, string $default): string
    {
        $value = config("permission.column_names.{$name}");

        return is_string($value) && $value !== '' ? $value : $default;
    }
};
