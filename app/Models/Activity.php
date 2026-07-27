<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * RULE(db): UUIDv7 keys — AGENTS.md.
 *
 * Spatie's model assumes an auto-increment key. Without HasUuids it never
 * learns the uuid the database issued, exactly as with Role and Permission.
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
     * @return array<int, string>
     */
    public static function distinctLogNames(): array
    {
        return static::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function distinctEvents(): array
    {
        return static::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->all();
    }

    /**
     * The shape the feed renders, and the shape the SSE stream will push.
     * Kept in one place so both cannot drift apart.
     *
     * @return array<string, mixed>
     */
    public function toFeedItem(): array
    {
        $changes = $this->attribute_changes ?? collect();

        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'event' => $this->event,
            'description' => $this->description,
            'subject_type' => $this->subject_type ? class_basename($this->subject_type) : null,
            'subject_id' => $this->subject_id,
            'causer' => $this->causer ? [
                'id' => $this->causer->id,
                'name' => $this->causer->name,
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
