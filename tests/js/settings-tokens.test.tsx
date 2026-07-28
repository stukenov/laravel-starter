import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * RULE(api): a token nobody can create is an API nobody has — AGENTS.md.
 *
 * The backend proves a token authenticates. This proves a person can get
 * one: that the plaintext appears with the warning attached, that the
 * list draws, and that revoking asks twice.
 *
 * The plaintext is the part worth checking at this level. It exists for
 * exactly one response — only the hash is stored — so a page that
 * rendered it without the warning would send people away from the one
 * screen where the value still exists.
 */
const mockProps = vi.hoisted(() => ({
    current: {
        auth: { user: { timezone: 'Asia/Almaty' }, abilities: [] as string[] },
        locale: 'ru',
        translations: {
            'tokens.title': 'Токены API',
            'tokens.description': 'Ключи для доступа',
            'tokens.name': 'Название',
            'tokens.name_hint': 'Чтобы понять, какой отзывать',
            'tokens.create': 'Создать токен',
            'tokens.revoke': 'Отозвать',
            'tokens.revoke_confirm': 'Точно отозвать?',
            'tokens.empty': 'Токенов пока нет.',
            'tokens.last_used': 'Использован',
            'tokens.never_used': 'Ни разу',
            'tokens.copy_once':
                'Скопируйте сейчас — второй раз он не покажется.',
            'tokens.copy': 'Скопировать',
            'tokens.copied': 'Скопировано',
        } as Record<string, string>,
    } as Record<string, unknown>,
}));

const deleted = vi.hoisted(() => ({ calls: [] as string[] }));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
    Form: ({ children }: { children: (bag: unknown) => React.ReactNode }) => (
        <form>{children({ errors: {}, processing: false })}</form>
    ),
    Link: ({ children, ...rest }: Record<string, unknown>) => (
        <a {...rest}>{children as React.ReactNode}</a>
    ),
    router: { delete: (url: string) => deleted.calls.push(url) },
}));

vi.mock('@/actions/App/Http/Controllers/Settings/TokenController', () => ({
    default: {
        store: { form: () => ({ action: '/settings/tokens', method: 'post' }) },
    },
}));

vi.mock('@/routes/tokens', () => ({
    index: () => '/settings/tokens',
    destroy: (id: string) => `/settings/tokens/${id}`,
}));

const { default: Tokens } = await import('@/pages/settings/tokens');

const token = (over: Record<string, unknown> = {}) => ({
    id: 'tok-1',
    name: 'Мобильное приложение',
    created_at: '2026-07-28T06:00:00+00:00',
    last_used_at: null,
    ...over,
});

describe('the tokens page', () => {
    beforeEach(() => {
        deleted.calls = [];
    });

    // ── Correct data ─────────────────────────────────────────────────

    it('lists a token by the name it was given', () => {
        render(<Tokens tokens={[token()]} />);

        expect(screen.getByText('Мобильное приложение')).toBeInTheDocument();
        expect(screen.getByTestId('token-create')).toBeInTheDocument();
    });

    it('shows a fresh token once, with the warning attached', () => {
        // Never shown again, so the warning is the feature.
        render(<Tokens tokens={[token()]} created="7|abcdefghijklmnop" />);

        expect(screen.getByTestId('token-plaintext')).toHaveTextContent(
            '7|abcdefghijklmnop',
        );
        expect(
            screen.getByText(/второй раз он не покажется/),
        ).toBeInTheDocument();
    });

    it('says when a token has never been used', () => {
        render(<Tokens tokens={[token()]} />);

        expect(screen.getByText(/Ни разу/)).toBeInTheDocument();
    });

    // ── Wrong data ───────────────────────────────────────────────────

    it('asks twice before revoking', async () => {
        // Revoking is irreversible and breaks whatever is using it.
        const user = userEvent.setup();

        render(<Tokens tokens={[token()]} />);

        await user.click(screen.getByTestId('token-revoke'));

        expect(deleted.calls).toEqual([]);
        expect(screen.getByTestId('token-revoke')).toHaveTextContent(
            'Точно отозвать?',
        );

        await user.click(screen.getByTestId('token-revoke'));

        expect(deleted.calls).toEqual(['/settings/tokens/tok-1']);
    });

    it('shows no plaintext when nothing was just created', () => {
        render(<Tokens tokens={[token()]} />);

        expect(screen.queryByTestId('token-plaintext')).not.toBeInTheDocument();
    });

    // ── Empty data ───────────────────────────────────────────────────

    it('says so when there are no tokens', () => {
        render(<Tokens tokens={[]} />);

        expect(screen.getByTestId('tokens-empty')).toBeInTheDocument();
        expect(screen.queryByTestId('token-row')).not.toBeInTheDocument();
    });

    it('still offers to create one when the list is empty', () => {
        // An empty state that hides the way out of it is a dead end.
        render(<Tokens tokens={[]} />);

        expect(screen.getByTestId('token-create')).toBeInTheDocument();
    });
});
