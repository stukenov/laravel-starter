import { cn } from '@/lib/utils';

/**
 * RULE(html): formatted text is cleaned on the way in, once — AGENTS.md.
 *
 * The one place in this project that renders unescaped HTML, and it is
 * allowed here because the value has already been through
 * `App\Support\Html\Sanitizer` on the way into the database. Nothing
 * reaching this component was written by a browser: it was written by
 * HTMLPurifier.
 *
 * `RawHtmlTest` names this file and refuses `dangerouslySetInnerHTML`
 * anywhere else. If you need formatted output somewhere new, render it
 * through this — the alternative is a second place to remember, and the
 * second place is the one that gets forgotten.
 *
 * Never pass it anything that has not been stored through a
 * `SanitizedHtml` cast. A string built at render time — a translation
 * with a placeholder, a value from another service — has been through
 * nothing.
 */
export default function SafeHtml({
    html,
    className,
}: {
    html: string | null | undefined;
    className?: string;
}) {
    if (!html) {
        return null;
    }

    return (
        <div
            className={cn('prose-content', className)}
            data-test="safe-html"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
