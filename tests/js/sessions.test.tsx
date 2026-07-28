import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: { timezone: 'Asia/Almaty' } },
        locale: 'ru',
        translations: {
            'sessions.title': 'Устройства и сессии',
            'sessions.description': 'Где вы вошли',
            'sessions.current': 'Текущее устройство',
            'sessions.unknown_browser': 'Неизвестный браузер',
            'sessions.unknown_ip': 'Адрес неизвестен',
            'sessions.last_active': 'Последняя активность',
            'sessions.logout_others': 'Выйти со всех остальных',
            'sessions.only_current': 'Вы вошли только с этого устройства.',
        },
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
    Form: ({ children }: { children: (p: unknown) => React.ReactNode }) =>
        children({ processing: false }),
}));

vi.mock('@/routes/sessions', () => ({ index: () => '/settings/sessions' }));
vi.mock('@/actions/App/Http/Controllers/Settings/SessionController', () => ({
    default: { destroy: { form: () => ({}) } },
}));

const { default: Sessions } = await import('@/pages/settings/sessions');

const NOON_UTC = '2026-07-27T12:00:00Z';

function session(overrides: Record<string, unknown> = {}) {
    return {
        id: 'a',
        browser: 'Chrome',
        platform: 'Mac',
        device: 'desktop',
        ip: '192.168.1.1',
        last_active_at: NOON_UTC,
        is_current: false,
        ...overrides,
    };
}

describe('страница сессий', () => {
    // Correct data
    it('shows the browser and platform', () => {
        render(<Sessions sessions={[session()]} />);

        expect(screen.getByText(/Chrome — Mac/)).toBeInTheDocument();
    });

    it('marks the current device', () => {
        render(<Sessions sessions={[session({ is_current: true })]} />);

        expect(screen.getByTestId('current-session')).toHaveTextContent(
            'Текущее устройство',
        );
    });

    it('renders the time in the user timezone', () => {
        render(<Sessions sessions={[session()]} />);

        // Almaty is UTC+5, so noon UTC is 17:00 there.
        expect(screen.getByText(/17:00/)).toBeInTheDocument();
    });

    it('offers to log out others when there is more than one device', () => {
        render(
            <Sessions
                sessions={[
                    session({ id: 'a', is_current: true }),
                    session({ id: 'b' }),
                ]}
            />,
        );

        expect(screen.getByTestId('logout-others')).toBeInTheDocument();
    });

    // Wrong data
    it('falls back to a label when the browser is unknown', () => {
        render(<Sessions sessions={[session({ browser: null })]} />);

        expect(screen.getByText(/Неизвестный браузер/)).toBeInTheDocument();
    });

    it('falls back to a label when the ip is missing', () => {
        render(<Sessions sessions={[session({ ip: null })]} />);

        expect(screen.getByText(/Адрес неизвестен/)).toBeInTheDocument();
    });

    it('does not print the word null on screen', () => {
        render(
            <Sessions
                sessions={[
                    session({ browser: null, platform: null, ip: null }),
                ]}
            />,
        );

        expect(screen.queryByText(/null/)).not.toBeInTheDocument();
    });

    // Empty data
    it('renders nothing but the heading for an empty list', () => {
        render(<Sessions sessions={[]} />);

        expect(screen.queryAllByTestId('session-row')).toHaveLength(0);
    });

    it('hides the logout button when only the current device is listed', () => {
        render(<Sessions sessions={[session({ is_current: true })]} />);

        expect(screen.queryByTestId('logout-others')).not.toBeInTheDocument();
        expect(
            screen.getByText('Вы вошли только с этого устройства.'),
        ).toBeInTheDocument();
    });
});
