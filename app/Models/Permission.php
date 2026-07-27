<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * See App\Models\Role for why HasUuids is not optional here.
 */
class Permission extends SpatiePermission
{
    use HasUuids;
}
