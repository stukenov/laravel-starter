<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RULE(tenancy): a scoped model reads and writes only its own tenant.
 * See AGENTS.md.
 *
 * Two halves, and both are needed. Reading is a global scope, so a query
 * that forgets to filter still cannot see across tenants. Writing fills
 * `tenant_id` on create, so a record cannot be born unowned by somebody
 * forgetting to set it.
 *
 * With tenancy off, `Tenancy::current()` is always null and both halves
 * do nothing at all. The column sits empty and the application behaves
 * exactly as it did before the feature existed — which is the point of
 * shipping it as an option.
 *
 * It lives in `App\Support` rather than `App\Models` because a trait is
 * not a model, and the architecture test says so out loud.
 *
 * **A raw query bypasses this.** `DB::table('posts')` has no model and
 * therefore no scope, which is one more reason controllers may not reach
 * the database directly. `TenancyTest` checks the models that carry this
 * trait; nothing can check a query somebody writes by hand.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenant = Tenancy::current();

            if ($tenant !== null) {
                $query->where($query->getModel()->qualifyColumn('tenant_id'), $tenant);
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') === null) {
                $model->setAttribute('tenant_id', Tenancy::current());
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Every tenant's rows, for the one place that legitimately needs
     * them: an operator's report, a console command, a migration.
     *
     * Named so it is obvious in a diff. `withoutGlobalScope('tenant')`
     * scattered through a codebase is how isolation stops meaning
     * anything.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeAcrossTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
