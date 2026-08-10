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
/**
 * The label for one value of an enum column.
 *
 * `t()` answers an unknown key with the key itself. That is right for a
 * missing sentence — somebody sees it and writes it — and wrong for an
 * enum, where the value comes from a row rather than from the code. A
 * generated list printed `tickets.status_options.null` at the reader for a
 * row whose value was absent.
 *
 * Absent reads as an em dash. An unrecognised value falls back to itself,
 * which at least says what is in the database.
 */
export function enumLabel(
    t: (key: string) => string,
    prefix: string,
    value: unknown,
): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const key = `${prefix}.${String(value)}`;
    const label = t(key);

    return label === key ? String(value) : label;
}

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
