import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const mockProps = vi.hoisted(() => ({
    current: {
        name: 'Laravel Starter',
        locale: 'ru',
        locales: ['ru', 'kk', 'en'],
        auth: { user: null },
        translations: {
            'public.sign_in': 'Войти',
            'public.privacy': 'Конфиденциальность',
            'public.terms': 'Условия',
            'legal.unfilled': 'Раздел не заполнен.',
            'legal.privacy.title': 'Политика конфиденциальности',
            'legal.privacy.sections.controller': 'Кто обрабатывает данные',
            'legal.privacy.sections.rights': 'Права пользователя',
        } as Record<string, string>,
    } as Record<string, unknown>,
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps.current }),
    Head: () => null,
    Link: ({ children, ...rest }: Record<string, unknown>) => (
        <a {...rest}>{children as React.ReactNode}</a>
    ),
    router: { visit: vi.fn() },
}));

vi.mock('@/routes', () => ({ login: () => '/login' }));

const { default: Home } = await import('@/pages/home');
const { default: LegalDocument } = await import('@/pages/legal/document');

describe('public chrome', () => {
    // Correct data
    it('shows the application name and a way in', () => {
        render(<Home />);

        expect(screen.getByTestId('home-title')).toHaveTextContent(
            'Laravel Starter',
        );
        expect(screen.getByTestId('public-login')).toHaveTextContent('Войти');
    });

    it('links the legal pages inside the current locale', () => {
        render(<Home />);

        expect(screen.getByTestId('public-privacy')).toHaveAttribute(
            'href',
            '/ru/privacy',
        );
        expect(screen.getByTestId('public-terms')).toHaveAttribute(
            'href',
            '/ru/terms',
        );
    });

    it('offers every supported language', () => {
        render(<Home />);

        expect(screen.getByTestId('locale-switcher')).toBeInTheDocument();
    });
});

describe('legal document', () => {
    // Correct data
    it('renders a section per heading', () => {
        render(
            <LegalDocument
                document="privacy"
                sections={['controller', 'rights']}
            />,
        );

        expect(screen.getAllByTestId('legal-section')).toHaveLength(2);
        expect(screen.getByTestId('legal-title')).toHaveTextContent(
            'Политика конфиденциальности',
        );
    });

    it('shows the text once it is written', () => {
        mockProps.current.translations = {
            ...(mockProps.current.translations as Record<string, string>),
            'legal.privacy.body.controller': 'ТОО «Пример», Алматы.',
        };

        render(<LegalDocument document="privacy" sections={['controller']} />);

        expect(screen.getByTestId('legal-body')).toHaveTextContent(
            'ТОО «Пример», Алматы.',
        );
    });

    // Wrong data
    it('never prints a raw translation key at a reader', () => {
        // A missing key renders as itself, which on a legal page would
        // read as gibberish rather than as an unfinished section.
        render(<LegalDocument document="privacy" sections={['rights']} />);

        expect(screen.queryByText(/legal\./)).not.toBeInTheDocument();
    });

    // Empty data
    it('says a section is unfilled instead of leaving it blank', () => {
        // Plausible filler on a legal page can ship unnoticed and read as
        // a real promise. This cannot be mistaken for one.
        render(<LegalDocument document="privacy" sections={['rights']} />);

        expect(screen.getByTestId('legal-unfilled')).toHaveTextContent(
            'Раздел не заполнен.',
        );
    });

    it('renders nothing but the title when there are no sections', () => {
        render(<LegalDocument document="privacy" sections={[]} />);

        expect(screen.queryAllByTestId('legal-section')).toHaveLength(0);
        expect(screen.getByTestId('legal-title')).toBeInTheDocument();
    });
});
