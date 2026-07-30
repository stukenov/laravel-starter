<?php

declare(strict_types=1);

namespace App\Support\Sessions;

use App\Models\User;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Illuminate\Support\Facades\DB;

/**
 * RULE(sessions): the list is read from the sessions table — AGENTS.md.
 *
 * The session driver must be database or there is nothing to show.
 * User-agent parsing lives here rather than in the controller: tests
 * need it, and so will the activity log.
 */
class SessionList
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forUser(User $user, string $currentSessionId): array
    {
        // Without this the detector returns codes like "AP" for "Apple".
        AbstractDeviceParser::setVersionTruncation(AbstractDeviceParser::VERSION_TRUNCATION_NONE);

        $rows = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        // Values are read out one by one at the boundary. The query builder
        // returns stdClass with no shape the analyser can verify, so the
        // coercion happens here, once, instead of being assumed downstream.
        return $rows
            ->map(function (object $row) use ($currentSessionId): array {
                $data = (array) $row;

                return self::describe(
                    id: self::text($data['id'] ?? null) ?? '',
                    agent: self::text($data['user_agent'] ?? null),
                    ip: self::text($data['ip_address'] ?? null),
                    lastActivity: self::number($data['last_activity'] ?? null),
                    currentSessionId: $currentSessionId,
                );
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function describe(
        string $id,
        ?string $agent,
        ?string $ip,
        int $lastActivity,
        string $currentSessionId,
    ): array {
        $detector = new DeviceDetector($agent ?? '');
        $detector->parse();

        return [
            'id' => $id,
            // Do not invent values — return null and let the UI supply
            // a label in its own language.
            'browser' => self::clean($detector->getClient('name')),
            'platform' => self::clean($detector->getOs('name')),
            'device' => self::clean($detector->getDeviceName()),
            'ip' => $ip,
            // ISO 8601 (UTC) — the client formats it in the user's zone.
            // See the time rule in AGENTS.md.
            'last_active_at' => now()->createFromTimestamp($lastActivity)->toIso8601String(),
            'is_current' => $id === $currentSessionId,
        ];
    }

    /**
     * Narrows a value coming out of the database to a string or null.
     */
    private static function text(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * The same at the other end: a timestamp out of the query builder is
     * an untyped value, and a session with no readable one is treated as
     * having been active at the epoch rather than crashing the page.
     */
    private static function number(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Ends every session of the user except the current one.
     *
     * Not logoutOtherDevices(): that rotates the password hash in the
     * session but leaves the rows in place, so the list keeps showing
     * devices that are already gone.
     *
     * It lives here rather than in the controller because reaching the
     * database from a controller is banned — ArchitectureTest enforces it.
     */
    public static function revokeOthers(User $user, string $currentSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * The library returns 'UNK' instead of nothing when it fails to
     * identify something. That string is meaningless and untranslatable,
     * so it becomes null and the UI supplies a label.
     */
    private static function clean(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || $value === 'UNK') {
            return null;
        }

        return $value;
    }
}
