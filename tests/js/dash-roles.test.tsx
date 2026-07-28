import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: { timezone: 'Asia/Almaty' } },
        locale: 'ru',
        translations: {
            'roles.title': 'Роли',
            'roles.description': 'Кто что может',
            'roles.add': 'Создать роль',
            'roles.edit': 'Настройка роли',
            'roles.name': 'Название',
            'roles.name_hint': 'Латиницей',
            'roles.permissions': 'Права',
            'roles.permissions_count': 'Прав',
            'roles.users_count': 'Пользователей',
            'roles.protected': 'Защищена',
            'roles.protected_hint': 'Роль администратора нельзя удалить',
            'roles.empty': 'Ролей пока нет',
            'roles.save': 'Сохранить',
            'roles.cancel': 'Отмена',
            'roles.delete': 'Удалить роль',
            'roles.abilities.users_view': 'Видеть пользователей',
            'roles.abilities.users_manage': 'Управлять пользователями',
        },
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
    Link: ({ children, ...rest }: Record<string, unknown>) => (
        <a {...rest}>{children as React.ReactNode}</a>
    ),
    router: { delete: vi.fn() },
    Form: ({ children }: { children: (p: unknown) => React.ReactNode }) =>
        children({ errors: {}, processing: false }),
}));

vi.mock('@/routes/dash/roles', () => ({
    index: () => '/dash/roles',
    edit: (id: string) => `/dash/roles/${id}`,
    destroy: (id: string) => `/dash/roles/${id}`,
}));
vi.mock('@/actions/App/Http/Controllers/Dash/RoleController', () => ({
    default: { store: { form: () => ({}) }, update: { form: () => ({}) } },
}));

const { default: RolesIndex } = await import('@/pages/dash/roles/index');
const { default: RoleEdit } = await import('@/pages/dash/roles/edit');

/** A paginator the way Laravel serialises one. */
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

function role(overrides: Record<string, unknown> = {}) {
    return {
        id: 'r1',
        name: 'admin',
        permissions_count: 5,
        users_count: 1,
        is_protected: true,
        ...overrides,
    };
}

describe('roles list', () => {
    // Correct data
    it('renders a row per role', () => {
        render(
            <RolesIndex
                roles={page([
                    role(),
                    role({ id: 'r2', name: 'member', is_protected: false }),
                ])}
            />,
        );

        expect(screen.getAllByTestId('roles-row')).toHaveLength(2);
    });

    it('marks the protected role', () => {
        render(<RolesIndex roles={page([role()])} />);

        expect(screen.getByText('Защищена')).toBeInTheDocument();
    });

    it('shows the counts', () => {
        render(
            <RolesIndex
                roles={page([role({ permissions_count: 3, users_count: 7 })])}
            />,
        );

        expect(screen.getByText('3')).toBeInTheDocument();
        expect(screen.getByText('7')).toBeInTheDocument();
    });

    // Wrong data
    it('does not offer a protected badge to an ordinary role', () => {
        render(<RolesIndex roles={page([role({ is_protected: false })])} />);

        expect(screen.queryByText('Защищена')).not.toBeInTheDocument();
    });

    // Empty data
    it('shows the empty state', () => {
        render(<RolesIndex roles={page([])} />);

        expect(screen.getByTestId('roles-empty')).toHaveTextContent(
            'Ролей пока нет',
        );
    });
});

describe('permission matrix', () => {
    const abilities = ['users.view', 'users.manage'];

    // Correct data
    it('renders a row per ability', () => {
        render(
            <RoleEdit
                role={{ id: 'r1', name: 'member', is_protected: false }}
                abilities={abilities}
                granted={[]}
            />,
        );

        expect(screen.getAllByTestId('ability-row')).toHaveLength(2);
    });

    it('translates ability names despite the dot in the key', () => {
        // A dot makes Laravel read the key as a nested path, so the label
        // lookup swaps it for an underscore.
        render(
            <RoleEdit
                role={{ id: 'r1', name: 'member', is_protected: false }}
                abilities={abilities}
                granted={[]}
            />,
        );

        expect(
            screen.getByText('Управлять пользователями'),
        ).toBeInTheDocument();
    });

    // Wrong data
    it('hides the delete button on a protected role', () => {
        render(
            <RoleEdit
                role={{ id: 'r1', name: 'admin', is_protected: true }}
                abilities={abilities}
                granted={abilities}
            />,
        );

        expect(screen.queryByTestId('role-delete')).not.toBeInTheDocument();
        expect(
            screen.getByText('Роль администратора нельзя удалить'),
        ).toBeInTheDocument();
    });

    it('offers deletion on an ordinary role', () => {
        render(
            <RoleEdit
                role={{ id: 'r2', name: 'member', is_protected: false }}
                abilities={abilities}
                granted={[]}
            />,
        );

        expect(screen.getByTestId('role-delete')).toBeInTheDocument();
    });

    // Empty data
    it('renders nothing but the name field when there are no abilities', () => {
        render(
            <RoleEdit
                role={{ id: 'r1', name: 'member', is_protected: false }}
                abilities={[]}
                granted={[]}
            />,
        );

        expect(screen.queryAllByTestId('ability-row')).toHaveLength(0);
        expect(screen.getByTestId('role-name')).toBeInTheDocument();
    });
});
