import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { makeTranslator } from '@/lib/i18n';

/**
 * ПРАВИЛО(локали): весь текст экранов идёт через t() — AGENTS.md.
 *
 * Словарь приходит общими свойствами Inertia, поэтому хук не делает
 * запросов и не хранит состояние — только оборачивает готовые данные.
 */
export function useTranslations() {
    const { translations } = usePage().props;

    return useMemo(() => makeTranslator(translations), [translations]);
}
