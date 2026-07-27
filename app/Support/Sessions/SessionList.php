<?php

namespace App\Support\Sessions;

use App\Models\User;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Illuminate\Support\Facades\DB;

/**
 * ПРАВИЛО(сессии): список читается из таблицы sessions — AGENTS.md.
 *
 * Драйвер сессий обязан быть database, иначе показывать нечего. Разбор
 * user-agent вынесен сюда, а не в контроллер: он нужен и в тестах, и,
 * скорее всего, в журнале действий.
 */
class SessionList
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forUser(User $user, string $currentSessionId): array
    {
        // Без этого детектор отдаёт коды вроде "AP" вместо "Apple".
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
            // Пустые значения не выдумываем — отдаём null, а подпись
            // подставляет интерфейс на своём языке.
            'browser' => self::clean($browser),
            'platform' => self::clean($platform),
            'device' => self::clean($device),
            'ip' => $row->ip_address,
            // Момент времени в ISO 8601 (UTC) — форматирует клиент
            // в зоне пользователя. См. правило про время в AGENTS.md.
            'last_active_at' => now()->createFromTimestamp($row->last_activity)->toIso8601String(),
            'is_current' => $row->id === $currentSessionId,
        ];
    }

    /**
     * Завершает все сессии пользователя, кроме текущей.
     *
     * Не logoutOtherDevices(): тот меняет хеш пароля в сессии, но строки
     * в таблице оставляет — список продолжит показывать устройства,
     * которых уже нет.
     *
     * Живёт здесь, а не в контроллере: обращаться к базе из контроллера
     * запрещено правилом, и это проверяет ArchitectureTest.
     */
    public static function revokeOthers(User $user, string $currentSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Библиотека отдаёт 'UNK' вместо пустоты, когда не смогла опознать.
     * Показывать это пользователю нельзя: строка ничего не значит и не
     * переводится. Приводим к null — подпись подставит интерфейс.
     */
    private static function clean(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || $value === 'UNK') {
            return null;
        }

        return $value;
    }
}
