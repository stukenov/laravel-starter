import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslations } from '@/hooks/use-translations';
import { cn, toUrl } from '@/lib/utils';
import { edit as editLocale } from '@/routes/locale';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as sessionsIndex } from '@/routes/sessions';
import { index as tokensIndex } from '@/routes/tokens';
import type { NavItem } from '@/types';

// RULE(theme): there is no "Appearance" item here and never will be —
// the theme is light only, nothing to switch. See AGENTS.md
const sidebarNavItems: NavItem[] = [
    {
        title: 'nav.profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'nav.security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'nav.locale',
        href: editLocale(),
        icon: null,
    },
    {
        title: 'nav.sessions',
        href: sessionsIndex(),
        icon: null,
    },
    {
        title: 'nav.tokens',
        href: tokensIndex(),
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const t = useTranslations();

    return (
        <div className="px-4 py-6">
            <Heading
                title={t('settings.title')}
                description={t('settings.description')}
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Settings"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {t(item.title)}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
