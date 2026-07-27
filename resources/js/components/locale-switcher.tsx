import { router, usePage } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const NAMES: Record<string, string> = {
    ru: 'Русский',
    kk: 'Қазақша',
    en: 'English',
};

/**
 * RULE(i18n): public pages carry the locale in the URL — AGENTS.md.
 *
 * Switching swaps the first path segment rather than calling a route
 * helper: the visitor stays on the page they were reading, whatever it
 * is. A full visit rather than an Inertia one, because the server has to
 * re-read the locale and set the cookie for pages outside the prefix.
 */
export function LocaleSwitcher() {
    const { locale, locales } = usePage().props;
    const supported = (locales as string[]) ?? [];

    const switchTo = (next: string) => {
        const [, current, ...rest] = window.location.pathname.split('/');

        const path = supported.includes(current)
            ? ['', next, ...rest].join('/')
            : `/${next}`;

        router.visit(path + window.location.search);
    };

    return (
        <Select value={locale as string} onValueChange={switchTo}>
            <SelectTrigger className="w-auto gap-2" data-test="locale-switcher">
                <SelectValue />
            </SelectTrigger>
            <SelectContent align="end">
                {supported.map((code) => (
                    <SelectItem key={code} value={code}>
                        {NAMES[code] ?? code}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
