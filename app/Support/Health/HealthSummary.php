<?php

declare(strict_types=1);

namespace App\Support\Health;

use Carbon\Carbon;
use Spatie\Health\Enums\Status;
use Spatie\Health\ResultStores\ResultStore;

/**
 * RULE(architecture): a controller may not reach the data itself — AGENTS.md.
 *
 * The panel page and the JSON endpoint answer the same question, so the
 * shape lives in one place and the two cannot drift apart.
 *
 * Nothing here runs a check. `health:check` writes results on the schedule
 * and this reads them: a page that ran them would fire a disk scan and a
 * database query on every refresh, and hang on a check that hangs.
 */
final class HealthSummary
{
    /**
     * @return array{checks: array<int, array<string, mixed>>, finished_at: ?string, healthy: bool}
     */
    public static function make(ResultStore $store): array
    {
        $stored = $store->latestResults();

        // Iterated rather than piped through `collect()`: the property is
        // already a typed collection, and wrapping it flattens that back
        // to `mixed` — every field below would then be read off nothing
        // in particular.
        $checks = [];

        foreach ($stored === null ? [] : $stored->storedCheckResults as $result) {
            $checks[] = [
                'name' => $result->name,
                'label' => $result->label,
                'status' => $result->status,
                'summary' => $result->shortSummary,
                'message' => $result->notificationMessage,
            ];
        }

        return [
            'checks' => $checks,
            // ISO 8601; the client formats it in the user's zone. Carbon by
            // the time it gets here, but typed loosely upstream.
            'finished_at' => $stored?->finishedAt
                ? Carbon::instance($stored->finishedAt)->toIso8601String()
                : null,
            // An empty result set is not healthy — it means the schedule
            // has never run, which is the thing worth knowing.
            //
            // `crashed` counts as broken, and it used not to. Spatie has
            // five statuses, and an unhandled exception inside a check
            // becomes `crashed`, not `failed` — so a database check that
            // threw was reported healthy, and /health.json answered 200
            // to the monitor at the moment everything was wrong. Verified
            // status by status before this was fixed.
            'healthy' => $checks !== []
                && collect($checks)->every(
                    fn (array $check) => ! in_array($check['status'], [
                        Status::failed()->value,
                        Status::crashed()->value,
                    ], true),
                ),
            // A warning is not a failure and must not page anybody at
            // three in the morning — the connection-count check warns
            // long before anything breaks. But it must not be invisible
            // either, which it was: the page showed one amber row under a
            // green banner and the monitor saw nothing at all.
            'degraded' => collect($checks)->contains(
                fn (array $check) => $check['status'] === Status::warning()->value,
            ),
        ];
    }
}
