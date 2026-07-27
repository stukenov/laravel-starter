import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: { timezone: 'Asia/Almaty' } },
        locale: 'ru',
        translations: {
            'health.title': 'Состояние',
            'health.description': 'Что проверяется и что сломалось',
            'health.healthy': 'Всё работает',
            'health.unhealthy': 'Есть проблемы',
            'health.never_ran': 'Проверки ещё ни разу не выполнялись.',
            'health.checked_at': 'Последняя проверка:',
            'health.status.ok': 'Норма',
        },
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
}));

const { default: HealthIndex } = await import('@/pages/dash/health/index');

function check(overrides: Record<string, unknown> = {}) {
    return {
        name: 'Database',
        label: 'База данных',
        status: 'ok',
        summary: 'Ok',
        message: null,
        ...overrides,
    };
}

describe('health page', () => {
    // Correct data
    it('renders a row per check', () => {
        render(
            <HealthIndex
                checks={[check(), check({ name: 'Cache', label: 'Кэш' })]}
                finished_at="2026-07-27T12:00:00Z"
                healthy
            />,
        );

        expect(screen.getAllByTestId('health-row')).toHaveLength(2);
    });

    it('says everything works when nothing failed', () => {
        render(
            <HealthIndex
                checks={[check()]}
                finished_at="2026-07-27T12:00:00Z"
                healthy
            />,
        );

        expect(screen.getByTestId('health-status')).toHaveTextContent(
            'Всё работает',
        );
    });

    it('shows the check time in the user timezone', () => {
        render(
            <HealthIndex
                checks={[check()]}
                finished_at="2026-07-27T12:00:00Z"
                healthy
            />,
        );

        // Noon UTC is 17:00 in Almaty — same day, so the date holds.
        expect(screen.getByTestId('health-finished-at')).toHaveTextContent(/27/);
    });

    // Wrong data
    it('says something is wrong when a check failed', () => {
        render(
            <HealthIndex
                checks={[
                    check(),
                    check({
                        name: 'Queue',
                        label: 'Очередь',
                        status: 'failed',
                        summary: 'Очередь не работает',
                    }),
                ]}
                finished_at="2026-07-27T12:00:00Z"
                healthy={false}
            />,
        );

        expect(screen.getByTestId('health-status')).toHaveTextContent(
            'Есть проблемы',
        );
        expect(screen.getByText('Очередь не работает')).toBeInTheDocument();
    });

    it('falls back to the message when there is no summary', () => {
        render(
            <HealthIndex
                checks={[
                    check({ summary: null, message: 'Развёрнутое объяснение' }),
                ]}
                finished_at="2026-07-27T12:00:00Z"
                healthy={false}
            />,
        );

        expect(
            screen.getByText('Развёрнутое объяснение'),
        ).toBeInTheDocument();
    });

    // Empty data
    it('says the checks never ran rather than showing an empty table', () => {
        // "No results" is not "all good": it means the scheduler is not
        // running, which is the thing worth saying out loud.
        render(<HealthIndex checks={[]} finished_at={null} healthy={false} />);

        expect(screen.getByTestId('health-empty')).toHaveTextContent(
            'Проверки ещё ни разу не выполнялись.',
        );
        expect(screen.queryAllByTestId('health-row')).toHaveLength(0);
    });

    it('prints no time when there is none', () => {
        render(<HealthIndex checks={[]} finished_at={null} healthy={false} />);

        expect(screen.queryByTestId('health-finished-at')).not.toBeInTheDocument();
        expect(screen.queryByText(/null/)).not.toBeInTheDocument();
    });
});
