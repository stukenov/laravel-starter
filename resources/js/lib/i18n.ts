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
        const line = translations[key] ?? key;

        // One pass, longest match wins. Replacing key by key looks
        // simpler and is wrong the moment one placeholder is a prefix of
        // another: `:grade` eats the front of `:grade_name` and the line
        // comes out as "II_name". Nothing errors — the text is just
        // quietly mangled, and only in the language that happens to have
        // both placeholders in one sentence.
        //
        // An unknown placeholder is left as it is, so a missing value
        // shows up on screen instead of becoming a blank.
        return line.replace(/:(\w+)/g, (whole, name: string) =>
            Object.hasOwn(replacements, name)
                ? String(replacements[name])
                : whole,
        );
    };
}
