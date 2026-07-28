import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: { timezone: 'Asia/Almaty' } },
        locale: 'ru',
        translations: {
            'docs.title': 'Docs',
            'docs.description': 'Список записей',
            'docs.search': 'Поиск',
            'docs.add': 'Добавить',
            'docs.empty': 'Записей пока нет',
            'docs.empty_search': 'Ничего не найдено',
        },
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
    router: { get: vi.fn() },
    Form: ({ children }: { children: (p: unknown) => React.ReactNode }) =>
        children({ errors: {}, processing: false }),
}));

vi.mock('@/routes/dash/docs', () => ({
    index: () => ({ url: '/dash/docs' }),
}));
vi.mock('@/actions/App/Http/Controllers/Dash/DocController', () => ({
    default: { store: { form: () => ({}) }, update: { form: () => ({}) } },
}));

const { default: Index } = await import('@/pages/dash/docs/index');

function record(overrides: Record<string, unknown> = {}) {
    return {
        id: '019fa2f8-0000-7000-8000-000000000001',
        title: 'Тестовая запись',
        created_at: '2026-07-27T12:00:00Z',
        ...overrides,
    };
}

describe('docs list', () => {
    // Correct data
    it('renders a row per record', () => {
        render(
            <Index
                search=""
                records={{ data: [record(), record({ id: 'b' })] }}
            />,
        );

        expect(screen.getAllByTestId('docs-row')).toHaveLength(2);
    });

    it('shows the value of the first field', () => {
        render(<Index search="" records={{ data: [record()] }} />);

        expect(screen.getByText('Тестовая запись')).toBeInTheDocument();
    });

    it('offers a way to add a record', () => {
        render(<Index search="" records={{ data: [] }} />);

        expect(screen.getByTestId('docs-add')).toHaveTextContent('Добавить');
    });

    // Wrong data
    it('does not print null for a missing value', () => {
        render(
            <Index search="" records={{ data: [record({ title: null })] }} />,
        );

        expect(screen.queryByText(/null/)).not.toBeInTheDocument();
    });

    it('survives a record with unexpected extra keys', () => {
        expect(() =>
            render(
                <Index
                    search=""
                    records={{ data: [record({ unexpected: 'x' })] }}
                />,
            ),
        ).not.toThrow();
    });

    // Empty data
    it('shows the empty state', () => {
        render(<Index search="" records={{ data: [] }} />);

        expect(screen.getByTestId('docs-empty')).toHaveTextContent(
            'Записей пока нет',
        );
    });

    it('tells the difference between empty and nothing found', () => {
        render(<Index search="что-то" records={{ data: [] }} />);

        expect(screen.getByTestId('docs-empty')).toHaveTextContent(
            'Ничего не найдено',
        );
    });

    it('renders no rows for an empty list', () => {
        render(<Index search="" records={{ data: [] }} />);

        expect(screen.queryAllByTestId('docs-row')).toHaveLength(0);
    });
});
