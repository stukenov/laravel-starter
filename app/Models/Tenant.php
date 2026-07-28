<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An organisation, when the application has more than one.
 *
 * Deliberately thin: a name and a slug. Everything a tenant *owns* hangs
 * off it through `tenant_id` on the other tables, so this model grows
 * only if a project needs it to — a plan, a locale, a logo.
 *
 * RULE(db): UUIDv7 keys, generated in two places on purpose. See AGENTS.md.
 */
#[Fillable(['name', 'slug'])]
class Tenant extends Model
{
    use HasUuids;

    /**
     * Not scoped by itself.
     *
     * A tenant cannot be filtered by the tenant it belongs to, and the
     * list of them is an operator's screen. Isolation happens on the
     * tables that point here.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
