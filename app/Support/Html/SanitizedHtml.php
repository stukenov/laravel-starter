<?php

namespace App\Support\Html;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * RULE(html): formatted text is cleaned on the way in, once — AGENTS.md.
 *
 * A cast rather than a mutator on each model, and a cast rather than a
 * line in the form request. A form request only guards the path a form
 * takes; this guards the column. The API writes through the same actions
 * today, but a seeder, a console command, a queued import and a future
 * second controller do not — and each of those is a way to put script
 * tags in the database that no form request ever sees.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class SanitizedHtml implements CastsAttributes
{
    /**
     * Read back exactly what was stored.
     *
     * Not cleaned again: it was cleaned on the way in, and cleaning on
     * every read would cost a full parse per row per page load for a
     * value that cannot have changed.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return Sanitizer::clean($value === null ? null : (string) $value);
    }
}
