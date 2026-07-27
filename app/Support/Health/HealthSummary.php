<?php

namespace App\Support\Health;

use Carbon\Carbon;
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

        $checks = collect($stored?->storedCheckResults)
            ->map(fn ($result) => [
                'name' => $result->name,
                'label' => $result->label,
                'status' => $result->status,
                'summary' => $result->shortSummary,
                'message' => $result->notificationMessage,
            ])
            ->values()
            ->all();

        return [
            'checks' => $checks,
            // ISO 8601; the client formats it in the user's zone. Carbon by
            // the time it gets here, but typed loosely upstream.
            'finished_at' => $stored?->finishedAt
                ? Carbon::instance($stored->finishedAt)->toIso8601String()
                : null,
            // An empty result set is not healthy — it means the schedule
            // has never run, which is the thing worth knowing.
            'healthy' => $checks !== []
                && collect($checks)->every(fn (array $check) => $check['status'] !== 'failed'),
        ];
    }
}
