import { render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

/**
 * RULE(seo): a title and description per locale — AGENTS.md.
 *
 * The description half was missing on every public page. A page with none
 * gets whatever the search engine scrapes off it, in whatever language it
 * happens to pick, and there are three here to pick from.
 *
 * Rendered rather than grepped: `<Head>` children go through Inertia, and
 * a `<meta>` written in the file is not proof that one reaches the page.
 */
const captured = vi.hoisted(() => ({ children: null as unknown }));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            name: 'Laravel Starter',
            locale: 'ru',
            locales: ['ru', 'kk', 'en'],
            auth: { user: null, abilities: [] },
            translations: {
                'seo.home': 'Описание главной',
                'nav.home': 'Главная',
                'legal.privacy': 'Конфиденциальность',
                'legal.terms': 'Условия',
            } as Record<string, string>,
        },
    }),
    Head: ({ children }: { children?: unknown }) => {
        captured.children = children;
        return null;
    },
    Link: ({ children, ...rest }: Record<string, unknown>) => (
        <a {...rest}>{children as React.ReactNode}</a>
    ),
}));

vi.mock('@/layouts/public-layout', () => ({
    default: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

const { default: Home } = await import('@/pages/home');

describe('the public home page', () => {
    // ── Correct data ─────────────────────────────────────────────────

    it('passes a description to the head', () => {
        render(<Home />);

        const meta = captured.children as { props?: Record<string, string> };

        expect(meta?.props?.name).toBe('description');
        expect(meta?.props?.content).toBe('Описание главной');
    });

    // ── Wrong data ───────────────────────────────────────────────────

    it('sends the resolved text, not the key', () => {
        render(<Home />);

        const meta = captured.children as { props?: Record<string, string> };

        expect(meta?.props?.content).not.toContain('seo.');
    });

    // ── Empty data ───────────────────────────────────────────────────

    it('renders a head at all', () => {
        captured.children = null;

        render(<Home />);

        expect(captured.children).not.toBeNull();
    });
});
