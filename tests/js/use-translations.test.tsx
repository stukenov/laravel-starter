import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {} as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
}));

const { useTranslations } = await import('@/hooks/use-translations');

function Probe({ k }: { k: string }) {
    const t = useTranslations();

    return <span data-test="out">{t(k)}</span>;
}

describe('useTranslations', () => {
    // Правильные данные
    it('reads the dictionary from shared props', () => {
        mockProps.current = { translations: { 'auth.login': 'Войти' } };

        render(<Probe k="auth.login" />);

        expect(screen.getByTestId('out')).toHaveTextContent('Войти');
    });

    it('serves kazakh text unchanged', () => {
        mockProps.current = { translations: { 'auth.login': 'Кіру' } };

        render(<Probe k="auth.login" />);

        expect(screen.getByTestId('out')).toHaveTextContent('Кіру');
    });

    // Неправильные данные
    it('falls back to the key when the translation is missing', () => {
        mockProps.current = { translations: { 'auth.login': 'Войти' } };

        render(<Probe k="auth.nope" />);

        expect(screen.getByTestId('out')).toHaveTextContent('auth.nope');
    });

    // Пустые данные
    it('survives an empty dictionary', () => {
        mockProps.current = { translations: {} };

        expect(() => render(<Probe k="anything" />)).not.toThrow();
    });
});
