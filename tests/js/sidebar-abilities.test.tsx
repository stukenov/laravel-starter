import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: { name: 'Сакен' }, abilities: [] as string[] },
        name: 'Laravel Starter',
        sidebarOpen: true,
        translations: {
            'nav.section': 'Разделы',
            'nav.dashboard': 'Панель',
            'users.title': 'Пользователи',
            'roles.title': 'Роли',
            'activity.title': 'Журнал действий',
            'health.title': 'Состояние',
        } as Record<string, string>,
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Link: ({ children, ...rest }: Record<string, unknown>) => (
        <a {...rest}>{children as React.ReactNode}</a>
    ),
    router: { post: vi.fn() },
}));

vi.mock('@/routes/dash', () => ({ home: () => '/dash' }));
vi.mock('@/routes/dash/users', () => ({ index: () => '/dash/users' }));
vi.mock('@/routes/dash/roles', () => ({ index: () => '/dash/roles' }));
vi.mock('@/routes/dash/activity', () => ({ index: () => '/dash/activity' }));
vi.mock('@/routes/dash/health', () => ({ index: () => '/dash/health' }));
vi.mock('@/components/nav-user', () => ({ NavUser: () => null }));

const { AppSidebar } = await import('@/components/app-sidebar');
const { SidebarProvider } = await import('@/components/ui/sidebar');
// The sidebar's collapsed items are tooltips, and Radix requires the
// provider. In the app it comes from `withApp` in app.tsx.
const { TooltipProvider } = await import('@/components/ui/tooltip');

function show(abilities: string[]) {
    (mockProps.current.auth as { abilities: string[] }).abilities = abilities;

    render(
        <TooltipProvider>
            <SidebarProvider>
                <AppSidebar />
            </SidebarProvider>
        </TooltipProvider>,
    );
}

describe('what the navigation offers', () => {
    // The panel used to offer every section to everybody and let the
    // server say no: a member saw Users, Roles, Activity and Health and
    // got 403 from each.

    // Correct data
    it('shows a section the person may open', () => {
        show(['users.view']);

        expect(screen.getByText('Пользователи')).toBeInTheDocument();
    });

    it('shows everything to somebody who may do everything', () => {
        show(['users.view', 'roles.view', 'activity.view', 'health.view']);

        for (const section of [
            'Пользователи',
            'Роли',
            'Журнал действий',
            'Состояние',
        ]) {
            expect(screen.getByText(section)).toBeInTheDocument();
        }
    });

    it('always shows the panel home, which needs no permission', () => {
        show([]);

        expect(screen.getByText('Панель')).toBeInTheDocument();
    });

    // Wrong data
    it('hides a section that would answer 403', () => {
        show(['users.view']);

        expect(screen.queryByText('Роли')).not.toBeInTheDocument();
        expect(screen.queryByText('Состояние')).not.toBeInTheDocument();
    });

    it('is not fooled by a permission for something else', () => {
        show(['activity.view']);

        expect(screen.getByText('Журнал действий')).toBeInTheDocument();
        expect(screen.queryByText('Пользователи')).not.toBeInTheDocument();
    });

    // Empty data
    it('offers nothing but the home to somebody with no permissions', () => {
        show([]);

        for (const section of [
            'Пользователи',
            'Роли',
            'Журнал действий',
            'Состояние',
        ]) {
            expect(screen.queryByText(section)).not.toBeInTheDocument();
        }
    });
});
