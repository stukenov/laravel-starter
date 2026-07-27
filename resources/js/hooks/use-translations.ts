import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { makeTranslator } from '@/lib/i18n';

/**
 * RULE(i18n): every string on screen goes through t() — AGENTS.md.
 *
 * The dictionary arrives in Inertia shared props, so this hook makes no
 * requests and holds no state — it only wraps data that is already here.
 */
export function useTranslations() {
    const { translations } = usePage().props;

    return useMemo(() => makeTranslator(translations), [translations]);
}
