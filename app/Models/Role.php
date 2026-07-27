<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * RULE(db): UUIDv7 keys, generated in two places on purpose — AGENTS.md.
 *
 * Spatie's own model assumes an auto-increment key. Without HasUuids the
 * model never learns the uuid the database issued and hands a lastInsertId
 * artefact to the pivot query, which then fails with "no operator matches
 * uuid and integer". The same trap the User model already documents.
 */
class Role extends SpatieRole
{
    use HasUuids;
}
