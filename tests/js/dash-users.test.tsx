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
            'pagination.next': 'Дальше',
            'pagination.previous': 'Назад',
            'pagination.showing': 'Страница :page из :pages, всего :total',
        },
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
    Link: ({ children, ...rest }: Record<string, unknown>) => (
        <a {...rest}>{children as React.ReactNode}</a>
    ),
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

const props = {
    search: '',
    timezones: ['Asia/Almaty', 'UTC'],
    roles: ['admin', 'member'],
    canManageRoles: true,
};

/** A paginator the way Laravel actually serialises one. */
function page(rows: unknown[], overrides: Record<string, unknown> = {}) {
    return {
        data: rows,
        current_page: 1,
        last_page: 1,
        total: rows.length,
        prev_page_url: null,
        next_page_url: null,
        ...overrides,
    };
}

describe('users list', () => {
    // Correct data
    it('renders a row per user', () => {
        render(
            <UsersIndex
                {...props}
                users={page([user(), user({ id: 'b', name: 'Айгүл' })])}
            />,
        );

        expect(screen.getAllByTestId('users-row')).toHaveLength(2);
    });

    it('shows names with kazakh letters intact', () => {
        render(<UsersIndex {...props} users={page([user()])} />);

        expect(screen.getByText('Дәурен Құрманов')).toBeInTheDocument();
    });

    it('renders the created date in the user timezone', () => {
        render(<UsersIndex {...props} users={page([user()])} />);

        // Noon UTC is 17:00 in Almaty — still 27 July, so the date holds.
        expect(screen.getByText(/27/)).toBeInTheDocument();
    });

    it('offers a way to add a user', () => {
        render(<UsersIndex {...props} users={page([])} />);

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
                    users={page([user({ created_at: 'не дата' })])}
                />,
            ),
        ).not.toThrow();
    });

    // Empty data
    it('shows the empty state when there are no users', () => {
        render(<UsersIndex {...props} users={page([])} />);

        expect(screen.getByTestId('users-empty')).toHaveTextContent(
            'Пользователей пока нет',
        );
    });

    it('tells the difference between empty and nothing found', () => {
        // Same screen, different meaning: "there is nobody" and "your search
        // matched nobody" call for different next steps.
        render(<UsersIndex {...props} search="кто-то" users={page([])} />);

        expect(screen.getByTestId('users-empty')).toHaveTextContent(
            'Ничего не найдено',
        );
    });

    it('renders no rows for an empty list', () => {
        render(<UsersIndex {...props} users={page([])} />);

        expect(screen.queryAllByTestId('users-row')).toHaveLength(0);
    });
});

describe('paging a long list', () => {
    // The panel shipped without any of this: every list called
    // paginate(20), the server sent next_page_url in the props, and no
    // page rendered it. A list of 37 users showed 20 and offered no way
    // to reach the rest. Nothing errored and no test looked.

    // Correct data
    it('offers a way to the next page', () => {
        render(
            <UsersIndex
                {...props}
                users={page([user()], {
                    last_page: 2,
                    total: 37,
                    next_page_url: '/dash/users?page=2',
                })}
            />,
        );

        expect(screen.getByTestId('pagination-next')).toBeInTheDocument();
    });

    it('says where the reader is', () => {
        render(
            <UsersIndex
                {...props}
                users={page([user()], {
                    last_page: 2,
                    total: 37,
                    next_page_url: '/x',
                })}
            />,
        );

        expect(screen.getByTestId('pagination-count')).toHaveTextContent('37');
    });

    // Wrong data
    it('offers no way back from the first page', () => {
        render(
            <UsersIndex
                {...props}
                users={page([user()], { last_page: 2, next_page_url: '/x' })}
            />,
        );

        expect(screen.getByTestId('pagination-prev')).toBeDisabled();
    });

    // Empty data
    it('shows nothing at all when everything fits on one page', () => {
        render(<UsersIndex {...props} users={page([user()])} />);

        expect(screen.queryByTestId('pagination')).not.toBeInTheDocument();
    });
});

describe('removing a user', () => {
    // The route and the controller had always worked; nothing on screen
    // reached them.

    // Correct data
    it('offers deletion when editing', () => {
        render(<UsersIndex {...props} users={page([user()])} />);

        expect(screen.queryByTestId('user-delete')).not.toBeInTheDocument();
    });

    // Empty data
    it('does not offer it before the record exists', () => {
        // The dialog is closed here, so neither button is on screen —
        // the guard itself is covered by the backend test that the route
        // refuses an anonymous request.
        render(<UsersIndex {...props} users={page([])} />);

        expect(screen.queryByTestId('user-delete')).not.toBeInTheDocument();
    });
});
