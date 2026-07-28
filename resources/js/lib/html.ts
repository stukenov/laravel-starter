/**
 * A plain-text excerpt of stored formatted text, for a list row.
 *
 * A row is one line: headings, lists and quotes would wreck it, and the
 * markup printed as text is worse than either. So the tags come out and
 * the words stay.
 *
 * Not a security function and not a substitute for one. The value has
 * already been cleaned on the way into the database — see
 * `App\Support\Html\Sanitizer`. This is only about what fits on a line,
 * which is why a regex is enough and why it runs during SSR, where there
 * is no DOM to parse with.
 */
export function excerpt(html: string | null | undefined, limit = 120): string {
    if (!html) {
        return '';
    }

    const text = html
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/\s+/g, ' ')
        .trim();

    return text.length > limit ? `${text.slice(0, limit).trimEnd()}…` : text;
}
