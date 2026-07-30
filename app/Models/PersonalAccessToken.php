<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * RULE(db): UUIDv7 keys — AGENTS.md.
 *
 * Sanctum's model assumes an auto-increment key. Without HasUuids it
 * never learns the uuid the database issued, exactly as with Role,
 * Permission and Activity before it. Registered in
 * `AppServiceProvider::boot` through `Sanctum::usePersonalAccessTokenModel`.
 */
class PersonalAccessToken extends SanctumToken
{
    use HasUuids;
}
