import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { useTranslations } from '@/hooks/use-translations';
import { login } from '@/routes';

/**
 * RULE(design): public pages are the 17px density — AGENTS.md.
 *
 * Header and footer for everything outside the cabinet. Links are built
 * from the locale in shared props rather than a route helper, because
 * every public address is `/{locale}/…` and the visitor must stay in the
 * language they are reading.
 */
export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const t = useTranslations();
    const { locale, name } = usePage().props;
    const home = `/${locale}`;

    return (
        <div className="density-public flex min-h-svh flex-col">
            <header className="border-b border-border">
                <div className="mx-auto flex h-16 w-full max-w-5xl items-center justify-between gap-4 px-6">
                    <Link
                        href={home}
                        className="flex items-center gap-2 font-semibold"
                        data-test="public-home"
                    >
                        <span className="flex size-7 items-center justify-center rounded-md bg-primary">
                            <AppLogoIcon className="size-4 fill-current text-primary-foreground" />
                        </span>
                        {name as string}
                    </Link>

                    <div className="flex items-center gap-3">
                        <LocaleSwitcher />
                        <Link
                            href={login()}
                            className="text-sm text-primary hover:underline"
                            data-test="public-login"
                        >
                            {t('public.sign_in')}
                        </Link>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-6 py-12">
                {children}
            </main>

            <footer className="border-t border-border">
                <div className="mx-auto flex w-full max-w-5xl flex-col gap-3 px-6 py-6 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        © {new Date().getFullYear()} {name as string}
                    </span>
                    <nav className="flex gap-4">
                        <Link
                            href={`${home}/privacy`}
                            className="hover:text-foreground"
                            data-test="public-privacy"
                        >
                            {t('public.privacy')}
                        </Link>
                        <Link
                            href={`${home}/terms`}
                            className="hover:text-foreground"
                            data-test="public-terms"
                        >
                            {t('public.terms')}
                        </Link>
                    </nav>
                </div>
            </footer>
        </div>
    );
}
