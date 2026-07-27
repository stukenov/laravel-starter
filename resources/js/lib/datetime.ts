/**
 * RULE(time): the client formats, the server does not — AGENTS.md.
 *
 * The server sends an instant in ISO 8601 (UTC); the user's zone lives
 * in their profile. Built-in Intl does the rest — no dayjs, no moment,
 * no date-fns, which also keeps the "nothing external" rule.
 *
 * A broken or empty value renders as a dash, not "Invalid Date": an
 * empty cell beats an English error in the middle of a Russian UI.
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
        // An unknown zone or locale must not take the page down:
        // show the same instant in UTC rather than nothing.
        return new Intl.DateTimeFormat('en', {
            ...options,
            timeZone: 'UTC',
        }).format(date);
    }
}
