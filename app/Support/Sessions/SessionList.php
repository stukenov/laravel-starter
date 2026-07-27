<?php

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

        return $rows->map(fn ($row) => self::describe($row, $currentSessionId))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function describe(object $row, string $currentSessionId): array
    {
        $agent = (string) ($row->user_agent ?? '');

        $detector = new DeviceDetector($agent);
        $detector->parse();

        $browser = $detector->getClient('name');
        $platform = $detector->getOs('name');
        $device = $detector->getDeviceName();

        return [
            'id' => $row->id,
            // Do not invent values — return null and let the UI supply
            // a label in its own language.
            'browser' => self::clean($browser),
            'platform' => self::clean($platform),
            'device' => self::clean($device),
            'ip' => $row->ip_address,
            // ISO 8601 (UTC) — the client formats it in the user's zone.
            // See the time rule in AGENTS.md.
            'last_active_at' => now()->createFromTimestamp($row->last_activity)->toIso8601String(),
            'is_current' => $row->id === $currentSessionId,
        ];
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
