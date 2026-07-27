import { useTranslations } from '@/hooks/use-translations';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

/**
 * RULE(i18n): the title is a key, not text — AGENTS.md.
 *
 * A page declares its layout as a static property (Login.layout = {...})
 * and a static object cannot call a hook. So it holds a translation key
 * and the layout resolves it, where the hook is available.
 */
export default function AuthLayout({
    titleKey = '',
    descriptionKey = '',
    children,
}: {
    titleKey?: string;
    descriptionKey?: string;
    children: React.ReactNode;
}) {
    const t = useTranslations();

    return (
        <AuthLayoutTemplate
            title={titleKey ? t(titleKey) : ''}
            description={descriptionKey ? t(descriptionKey) : ''}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
