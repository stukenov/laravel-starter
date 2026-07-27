import { describe, expect, it } from 'vitest';
import { makeTranslator } from '@/lib/i18n';

describe('makeTranslator', () => {
    // Correct data
    it('returns the translation for a known key', () => {
        const t = makeTranslator({ 'auth.login': 'Войти' });

        expect(t('auth.login')).toBe('Войти');
    });

    it('substitutes named placeholders', () => {
        const t = makeTranslator({ greeting: 'Привет, :name' });

        expect(t('greeting', { name: 'Сакен' })).toBe('Привет, Сакен');
    });

    it('substitutes the same placeholder more than once', () => {
        const t = makeTranslator({ echo: ':word и ещё раз :word' });

        expect(t('echo', { word: 'да' })).toBe('да и ещё раз да');
    });

    it('accepts numbers as replacements', () => {
        const t = makeTranslator({ count: 'Найдено: :n' });

        expect(t('count', { n: 42 })).toBe('Найдено: 42');
    });

    it('keeps kazakh letters intact', () => {
        const t = makeTranslator({ 'nav.dashboard': 'Басқару тақтасы' });

        expect(t('nav.dashboard')).toBe('Басқару тақтасы');
    });

    // Wrong data
    it('returns the key itself when the translation is missing', () => {
        // Пропущенный перевод должно быть видно на экране, а не превращаться
        // в пустое место — иначе он доедет до прода незамеченным.
        const t = makeTranslator({ 'auth.login': 'Войти' });

        expect(t('nope.missing')).toBe('nope.missing');
    });

    it('leaves an unknown placeholder untouched', () => {
        const t = makeTranslator({ greeting: 'Привет, :name' });

        expect(t('greeting', { other: 'x' })).toBe('Привет, :name');
    });

    // Empty data
    it('handles an empty dictionary without throwing', () => {
        const t = makeTranslator({});

        expect(() => t('anything')).not.toThrow();
        expect(t('anything')).toBe('anything');
    });

    it('handles an empty key', () => {
        const t = makeTranslator({});

        expect(t('')).toBe('');
    });

    it('handles an empty replacement value', () => {
        const t = makeTranslator({ greeting: 'Привет, :name' });

        expect(t('greeting', { name: '' })).toBe('Привет, ');
    });
});
