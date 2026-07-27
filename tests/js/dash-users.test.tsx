import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: { timezone: 'Asia/Almaty' } },
        locale: 'ru',
        locales: ['ru', 'kk', 'en'],
        translations: {
            'users.title': 'Пользователи',
            'users.description': 'Кто имеет доступ',
            'users.search': 'Поиск',
            'users.add': 'Добавить пользователя',
            'users.empty': 'Пользователей пока нет',
            'users.empty_search': 'Ничего не найдено',
            'users.name': 'Имя',
            'users.email': 'Почта',
            'users.locale': 'Язык',
            'users.created_at': 'Создан',
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

vi.mock('@/routes/dash/users', () => ({
    index: () => ({ url: '/dash/users' }),
}));
vi.mock('@/actions/App/Http/Controllers/Dash/UserController', () => ({
    default: {
        store: { form: () => ({}) },
        update: { form: () => ({}) },
    },
}));

const { default: UsersIndex } = await import('@/pages/dash/users/index');

function user(overrides: Record<string, unknown> = {}) {
    return {
        id: '019fa2f8-0000-7000-8000-000000000001',
        name: 'Дәурен Құрманов',
        email: 'dauren@example.kz',
        locale: 'ru',
        timezone: 'Asia/Almaty',
        email_verified_at: null,
        created_at: '2026-07-27T12:00:00Z',
        ...overrides,
    };
}

const props = { search: '', timezones: ['Asia/Almaty', 'UTC'] };

describe('users list', () => {
    // Correct data
    it('renders a row per user', () => {
        render(
            <UsersIndex
                {...props}
                users={{ data: [user(), user({ id: 'b', name: 'Айгүл' })], total: 2 }}
            />,
        );

        expect(screen.getAllByTestId('users-row')).toHaveLength(2);
    });

    it('shows names with kazakh letters intact', () => {
        render(<UsersIndex {...props} users={{ data: [user()], total: 1 }} />);

        expect(screen.getByText('Дәурен Құрманов')).toBeInTheDocument();
    });

    it('renders the created date in the user timezone', () => {
        render(<UsersIndex {...props} users={{ data: [user()], total: 1 }} />);

        // Noon UTC is 17:00 in Almaty — still 27 July, so the date holds.
        expect(screen.getByText(/27/)).toBeInTheDocument();
    });

    it('offers a way to add a user', () => {
        render(<UsersIndex {...props} users={{ data: [], total: 0 }} />);

        expect(screen.getByTestId('users-add')).toHaveTextContent(
            'Добавить пользователя',
        );
    });

    // Wrong data
    it('does not print null instead of a missing date', () => {
        render(
            <UsersIndex
                {...props}
                users={{ data: [user({ created_at: null })], total: 1 }}
            />,
        );

        expect(screen.queryByText(/null/)).not.toBeInTheDocument();
    });

    it('survives a broken date value', () => {
        expect(() =>
            render(
                <UsersIndex
                    {...props}
                    users={{ data: [user({ created_at: 'не дата' })], total: 1 }}
                />,
            ),
        ).not.toThrow();
    });

    // Empty data
    it('shows the empty state when there are no users', () => {
        render(<UsersIndex {...props} users={{ data: [], total: 0 }} />);

        expect(screen.getByTestId('users-empty')).toHaveTextContent(
            'Пользователей пока нет',
        );
    });

    it('tells the difference between empty and nothing found', () => {
        // Same screen, different meaning: "there is nobody" and "your search
        // matched nobody" call for different next steps.
        render(
            <UsersIndex
                {...props}
                search="кто-то"
                users={{ data: [], total: 0 }}
            />,
        );

        expect(screen.getByTestId('users-empty')).toHaveTextContent(
            'Ничего не найдено',
        );
    });

    it('renders no rows for an empty list', () => {
        render(<UsersIndex {...props} users={{ data: [], total: 0 }} />);

        expect(screen.queryAllByTestId('users-row')).toHaveLength(0);
    });
});
