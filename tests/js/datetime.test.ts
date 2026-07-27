import { describe, expect, it } from 'vitest';
import { formatDate, formatDateTime } from '@/lib/datetime';

const NOON_UTC = '2026-07-27T12:00:00Z';

describe('formatDateTime', () => {
    // Правильные данные
    it('shifts UTC into the user timezone', () => {
        // Алматы это UTC+5, значит полдень по UTC — это 17:00 на месте.
        expect(formatDateTime(NOON_UTC, 'Asia/Almaty', 'ru')).toContain('17:00');
    });

    it('renders the same instant differently per timezone', () => {
        const almaty = formatDateTime(NOON_UTC, 'Asia/Almaty', 'ru');
        const utc = formatDateTime(NOON_UTC, 'UTC', 'ru');

        expect(almaty).not.toBe(utc);
    });

    it('crosses the date boundary correctly', () => {
        // 22:00 UTC это уже следующий день в Алматы (03:00).
        const result = formatDateTime('2026-07-27T22:00:00Z', 'Asia/Almaty', 'ru');

        expect(result).toContain('28');
    });

    it('formats date only without a time part', () => {
        const result = formatDate(NOON_UTC, 'Asia/Almaty', 'ru');

        expect(result).not.toContain(':');
    });

    // Неправильные данные
    it('returns a dash for an unparseable value', () => {
        expect(formatDateTime('не дата', 'Asia/Almaty', 'ru')).toBe('—');
    });

    it('does not throw on an unknown timezone', () => {
        expect(() =>
            formatDateTime(NOON_UTC, 'Mars/Olympus', 'ru'),
        ).not.toThrow();
    });

    it('falls back to UTC on an unknown timezone', () => {
        const result = formatDateTime(NOON_UTC, 'Mars/Olympus', 'ru');

        expect(result).toContain('12:00');
    });

    it('does not throw on an unknown locale', () => {
        expect(() =>
            formatDateTime(NOON_UTC, 'Asia/Almaty', 'zz-ZZ-invalid'),
        ).not.toThrow();
    });

    // Пустые данные
    it('returns a dash for null', () => {
        expect(formatDateTime(null, 'Asia/Almaty', 'ru')).toBe('—');
    });

    it('returns a dash for undefined', () => {
        expect(formatDateTime(undefined, 'Asia/Almaty', 'ru')).toBe('—');
    });

    it('returns a dash for an empty string', () => {
        expect(formatDateTime('', 'Asia/Almaty', 'ru')).toBe('—');
    });
});
