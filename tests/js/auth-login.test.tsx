import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

/**
 * `REGISTRATION_ENABLED=false` unregisters the sign-up route, and this
 * page went on offering the link anyway — the one public entrance to a
 * closed application, ending in a 404, on exactly the deployment that had
 * been configured most carefully.
 *
 * The backend test proves the flag reaches the page. This one proves the
 * page acts on it, which is a different claim: a prop nothing reads is
 * indistinguishable from a prop nobody sends.
 */
const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: null, abilities: [] as string[] },
        name: 'Laravel Starter',
        registrationEnabled: true,
        translations: {
            'auth.login': 'Вход',
            'auth.email': 'Почта',
            'auth.password': 'Пароль',
            'auth.remember': 'Запомнить меня',
            'auth.forgot': 'Забыли пароль?',
            'auth.no_account': 'Нет аккаунта?',
            'auth.sign_up': 'Зарегистрироваться',
        } as Record<string, string>,
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
    // TextLink renders an Inertia Link, so the mock has to provide one.
    Link: ({ children, ...rest }: Record<string, unknown>) => (
        <a {...rest}>{children as React.ReactNode}</a>
    ),
    Form: ({ children }: { children: (bag: unknown) => React.ReactNode }) => (
        <form>{children({ errors: {}, processing: false })}</form>
    ),
}));

vi.mock('@/routes', () => ({ register: () => '/register' }));
// Wayfinder gives every route a `.form()` variant, and the page uses it
// to spread `action` and `method` onto the form — see AGENTS.md on why
// `--with-form` is not optional.
vi.mock('@/routes/login', () => ({
    store: Object.assign(() => '/login', {
        form: () => ({ action: '/login', method: 'post' }),
    }),
}));
vi.mock('@/routes/password', () => ({ request: () => '/forgot-password' }));

const { default: Login } = await import('@/pages/auth/login');

describe('the sign-in page', () => {
    // ── Correct data ─────────────────────────────────────────────────

    it('offers registration while it is open', () => {
        mockProps.current.registrationEnabled = true;

        render(<Login canResetPassword={true} />);

        expect(screen.getByTestId('login-signup')).toBeInTheDocument();
        expect(screen.getByText('Зарегистрироваться')).toBeInTheDocument();
    });

    it('still signs people in either way', () => {
        // The flag must hide one block and nothing else.
        mockProps.current.registrationEnabled = false;

        render(<Login canResetPassword={true} />);

        expect(screen.getByTestId('login-button')).toBeInTheDocument();
    });

    // ── Wrong data ───────────────────────────────────────────────────

    it('stops offering registration once it is closed', () => {
        mockProps.current.registrationEnabled = false;

        render(<Login canResetPassword={true} />);

        expect(screen.queryByTestId('login-signup')).not.toBeInTheDocument();
    });

    // ── Empty data ───────────────────────────────────────────────────

    it('says nothing about registration when the flag is missing', () => {
        // An old cached page, or a prop that failed to serialise:
        // undefined must read as closed, not as open. Offering a link
        // that 404s is worse than not offering one.
        delete mockProps.current.registrationEnabled;

        render(<Login canResetPassword={true} />);

        expect(screen.queryByTestId('login-signup')).not.toBeInTheDocument();

        mockProps.current.registrationEnabled = true;
    });
});
