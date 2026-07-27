export type Translations = Record<string, string>;

/**
 * ПРАВИЛО(локали): свой хелпер вместо библиотеки i18n — AGENTS.md.
 *
 * Переводы приходят общими свойствами Inertia уже плоским словарём,
 * поэтому весь нужный функционал — подстановка значений. Тянуть ради
 * этого внешнюю библиотеку незачем.
 *
 * Неизвестный ключ возвращается как есть: так пропущенный перевод видно
 * на экране сразу, а не превращается в пустое место.
 */
export function makeTranslator(translations: Translations) {
    return function t(
        key: string,
        replacements: Record<string, string | number> = {},
    ): string {
        let line = translations[key] ?? key;

        for (const [placeholder, value] of Object.entries(replacements)) {
            line = line.replaceAll(`:${placeholder}`, String(value));
        }

        return line;
    };
}
