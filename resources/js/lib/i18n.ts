export type Translations = Record<string, string>;

/**
 * RULE(i18n): our own helper instead of an i18n library — AGENTS.md.
 *
 * Translations arrive through Inertia shared props already flattened,
 * so the only work left is placeholder substitution. Not worth a
 * dependency.
 *
 * An unknown key comes back as itself, so a missing translation shows
 * up on screen instead of becoming a blank.
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
