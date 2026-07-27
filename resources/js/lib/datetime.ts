/**
 * ПРАВИЛО(время): форматирует клиент, не сервер — AGENTS.md.
 *
 * Сервер отдаёт момент времени в ISO 8601 (UTC), а зона пользователя лежит
 * в его профиле. Здесь используется встроенный Intl — ни dayjs, ни moment,
 * ни date-fns не нужны, и это же держит требование «ничего внешнего».
 *
 * Битое или пустое значение даёт тире, а не «Invalid Date»: пустая ячейка
 * в таблице лучше, чем англоязычная ошибка посреди русского интерфейса.
 */

const EMPTY = '—';

export function formatDateTime(
    iso: string | null | undefined,
    timezone: string,
    locale: string,
): string {
    return format(iso, locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: timezone,
    });
}

export function formatDate(
    iso: string | null | undefined,
    timezone: string,
    locale: string,
): string {
    return format(iso, locale, {
        dateStyle: 'medium',
        timeZone: timezone,
    });
}

function format(
    iso: string | null | undefined,
    locale: string,
    options: Intl.DateTimeFormatOptions,
): string {
    if (!iso) {
        return EMPTY;
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return EMPTY;
    }

    try {
        return new Intl.DateTimeFormat(locale, options).format(date);
    } catch {
        // Неизвестная зона или локаль не должны ронять страницу целиком:
        // показываем то же время в UTC, чем ничего.
        return new Intl.DateTimeFormat('en', { ...options, timeZone: 'UTC' }).format(date);
    }
}
