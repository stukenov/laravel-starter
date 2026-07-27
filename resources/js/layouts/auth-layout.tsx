import { useTranslations } from '@/hooks/use-translations';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

/**
 * ПРАВИЛО(локали): заголовок задаётся ключом, а не текстом — AGENTS.md.
 *
 * Страница объявляет лэйаут статическим свойством (Login.layout = {...}),
 * а в статике хук не вызвать. Поэтому туда кладётся ключ перевода,
 * а переводит уже лэйаут — здесь хук доступен.
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
