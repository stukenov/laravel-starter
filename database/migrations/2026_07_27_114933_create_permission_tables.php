<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $pivotRole = $columns['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columns['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tables), 'config/permission.php not loaded. Run php artisan config:clear.');

        Schema::create($tables['permissions'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['roles'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        DB::statement("alter table {$tables['permissions']} alter column id set default uuidv7()");
        DB::statement("alter table {$tables['roles']} alter column id set default uuidv7()");

        Schema::create($tables['model_has_permissions'], function (Blueprint $table) use ($tables, $columns, $pivotPermission) {
            $table->uuid($pivotPermission);
            $table->string('model_type');
            $table->uuid($columns['model_morph_key']);

            $table->index([$columns['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')->on($tables['permissions'])
                ->cascadeOnDelete();

            $table->primary(
                [$pivotPermission, $columns['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary',
            );
        });

        Schema::create($tables['model_has_roles'], function (Blueprint $table) use ($tables, $columns, $pivotRole) {
            $table->uuid($pivotRole);
            $table->string('model_type');
            $table->uuid($columns['model_morph_key']);

            $table->index([$columns['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')->on($tables['roles'])
                ->cascadeOnDelete();

            $table->primary(
                [$pivotRole, $columns['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary',
            );
        });

        Schema::create($tables['role_has_permissions'], function (Blueprint $table) use ($tables, $pivotRole, $pivotPermission) {
            $table->uuid($pivotPermission);
            $table->uuid($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')->on($tables['permissions'])
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id')->on($tables['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        Schema::dropIfExists($tables['role_has_permissions']);
        Schema::dropIfExists($tables['model_has_roles']);
        Schema::dropIfExists($tables['model_has_permissions']);
        Schema::dropIfExists($tables['roles']);
        Schema::dropIfExists($tables['permissions']);
    }
};
