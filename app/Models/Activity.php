<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * RULE(db): UUIDv7 keys — AGENTS.md.
 *
 * Spatie's model assumes an auto-increment key. Without HasUuids it never
 * learns the uuid the database issued, exactly as with Role and Permission.
 *
 * @property-read int $seq the monotonic cursor the SSE stream resumes from
 */
class Activity extends SpatieActivity
{
    use HasUuids;

    protected $casts = [
        'properties' => 'collection',
        'attribute_changes' => 'collection',
    ];

    /**
     * RULE(search): the query lives on the model — a controller may not
     * reach the database directly. See AGENTS.md.
     *
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function scopeFilter($query, string $log, string $event)
    {
        return $query
            ->when($log !== '', fn ($q) => $q->where('log_name', $log))
            ->when($event !== '', fn ($q) => $q->where('event', $event));
    }

    /**
     * The newest sequence in the log, or 0 when it is empty.
     *
     * Where a first connection starts. The page is rendered with the log
     * already in it, so replaying from zero would show every visible row
     * a second time.
     */
    public static function tip(): int
    {
        return (int) (static::max('seq') ?? 0);
    }

    /**
     * Everything written after the given cursor, oldest first.
     *
     * The sequence is a bigint rather than the uuid key: a browser
     * reconnecting sends Last-Event-ID as a plain string, and "greater
     * than N" has to be a cheap indexed comparison.
     *
     * @return Collection<int, static>
     */
    public static function since(int $seq)
    {
        return static::query()
            ->with('causer:id,name,email')
            ->where('seq', '>', $seq)
            ->orderBy('seq')
            ->limit(200)
            ->get();
    }

    /**
     * The values offered in the two filter dropdowns.
     *
     * Cached, because `distinct` over this table is a full scan and it ran
     * twice on every visit to the page. Measured against 200 000 rows:
     * 36 ms for the log names, 15 ms for the events — fifty milliseconds
     * of database time to fill two dropdowns whose contents change when a
     * new kind of event is logged, which is to say almost never.
     *
     * Five minutes is short enough that a new log name shows up while
     * somebody is still looking for it, and long enough that the scan
     * stops being per-request.
     *
     * @return array<int, string>
     */
    public static function distinctLogNames(): array
    {
        return Cache::remember(
            'activity.log_names',
            now()->addMinutes(5),
            fn () => static::query()
                ->whereNotNull('log_name')
                ->distinct()
                ->orderBy('log_name')
                ->pluck('log_name')
                ->all(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function distinctEvents(): array
    {
        return Cache::remember(
            'activity.events',
            now()->addMinutes(5),
            fn () => static::query()
                ->whereNotNull('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->all(),
        );
    }

    /** Both lists at once, for a test or a command that needs them fresh. */
    public static function forgetFilterOptions(): void
    {
        Cache::forget('activity.log_names');
        Cache::forget('activity.events');
    }

    /**
     * The shape the feed renders, and the shape the SSE stream will push.
     * Kept in one place so both cannot drift apart.
     *
     * @return array<string, mixed>
     */
    public function toFeedItem(): array
    {
        // causer is a morphTo and can point at anything; only a user has a
        // name to put on screen.
        $causer = $this->causer;

        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'event' => $this->event,
            'description' => $this->description,
            'subject_type' => $this->subject_type ? class_basename($this->subject_type) : null,
            'subject_id' => $this->subject_id,
            'causer' => $causer instanceof User ? [
                'id' => $causer->id,
                'name' => $causer->name,
            ] : null,
            'changes' => $this->readableChanges(),
            // ISO 8601 (UTC); the client formats it in the user's zone.
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Flattens old/attributes into one row per changed field. The raw shape
     * is two parallel objects, which is unreadable on screen and forces
     * every consumer to zip them itself.
     *
     * @return array<int, array{field: string, from: mixed, to: mixed}>
     */
    private function readableChanges(): array
    {
        $changes = $this->attribute_changes;

        if ($changes === null) {
            return [];
        }

        $old = (array) ($changes['old'] ?? []);
        $new = (array) ($changes['attributes'] ?? []);

        return collect($new)
            ->map(fn (mixed $value, string $field) => [
                'field' => $field,
                'from' => $old[$field] ?? null,
                'to' => $value,
            ])
            ->values()
            ->all();
    }
}
