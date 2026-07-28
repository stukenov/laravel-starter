import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

/**
 * RULE(html): formatted text is cleaned on the way in, once — AGENTS.md.
 *
 * What this level can answer: that the value reaches the form, and that
 * a list row shows words rather than markup. What it cannot answer is
 * whether anything is safe — that is the server's job and the
 * sanitizer's tests, because anybody can post straight to the endpoint
 * and never touch this component at all.
 */
const { default: SafeHtml } = await import('@/components/safe-html');
const { excerpt } = await import('@/lib/html');

describe('formatted text on screen', () => {
    // ── Correct data ─────────────────────────────────────────────────

    it('renders stored markup as markup', () => {
        render(<SafeHtml html="<p>Текст с <strong>акцентом</strong></p>" />);

        // The tag itself, not just the words: rendering it escaped would
        // show `<strong>` to the reader, which is the failure this
        // component exists to prevent.
        expect(screen.getByTestId('safe-html').innerHTML).toContain('<strong>');
        expect(screen.getByText('акцентом').tagName).toBe('STRONG');
    });

    it('reduces a document to one line for a list row', () => {
        const html =
            '<h2>Заголовок</h2><p>Первый абзац.</p><ul><li>пункт</li></ul>';

        expect(excerpt(html)).toBe('Заголовок Первый абзац. пункт');
    });

    it('cuts a long excerpt and says it was cut', () => {
        expect(excerpt('<p>'.concat('слово '.repeat(40), '</p>'), 20)).toMatch(
            /…$/,
        );
        expect(
            excerpt('<p>'.concat('слово '.repeat(40), '</p>'), 20).length,
        ).toBeLessThanOrEqual(21);
    });

    // ── Wrong data ───────────────────────────────────────────────────

    it('prints no tags in an excerpt', () => {
        // The row would otherwise show `<p>` to the reader.
        expect(excerpt('<p onclick="x()">текст</p>')).toBe('текст');
    });

    it('renders nothing at all for an empty value', () => {
        const { container } = render(<SafeHtml html="" />);

        expect(container).toBeEmptyDOMElement();
    });

    // ── Empty data ───────────────────────────────────────────────────

    it('returns an empty excerpt for nothing', () => {
        expect(excerpt(null)).toBe('');
        expect(excerpt(undefined)).toBe('');
        expect(excerpt('')).toBe('');
    });

    it('renders nothing for null', () => {
        const { container } = render(<SafeHtml html={null} />);

        expect(container).toBeEmptyDOMElement();
    });
});
