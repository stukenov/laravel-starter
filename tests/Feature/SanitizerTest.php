<?php

declare(strict_types=1);

use App\Support\Html\Sanitizer;

/**
 * RULE(html): formatted text is cleaned on the way in, once — AGENTS.md.
 *
 * On write, not on read. Cleaning on read means every place that displays
 * the text has to remember, and the one that forgets is the one that
 * ships. Cleaning on write means what the database holds is already safe
 * and every reader of it is safe without knowing this class exists.
 *
 * The editor is not part of this. Anybody can post whatever they like
 * straight to the endpoint, so what Tiptap offers in its toolbar proves
 * nothing about what arrives.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('keeps the formatting an author actually uses', function () {
    $html = '<p>Обычный <strong>текст</strong> и <em>наклонный</em>.</p>'
        .'<h2>Заголовок</h2><ul><li>пункт</li></ul><blockquote>цитата</blockquote>';

    expect(Sanitizer::clean($html))->toBe($html);
});

it('keeps a link and makes it safe to click', function () {
    // `noopener` is the only defence against the opened page rewriting
    // the one it came from through `window.opener`.
    $clean = Sanitizer::clean('<a href="https://example.com">ссылка</a>');

    expect($clean)->toContain('href="https://example.com"')
        ->and($clean)->toContain('noopener')
        ->and($clean)->toContain('target="_blank"');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('removes a script tag', function () {
    expect(Sanitizer::clean('<script>alert(1)</script><p>после</p>'))
        ->toBe('<p>после</p>');
});

it('removes an event handler', function () {
    // The attribute goes; the text a person wrote stays.
    expect(Sanitizer::clean('<p onclick="steal()">клик</p>'))
        ->toBe('<p>клик</p>');
});

it('removes a javascript link but keeps the words', function () {
    expect(Sanitizer::clean('<a href="javascript:alert(1)">ссылка</a>'))
        ->toBe('<a>ссылка</a>');
});

it('refuses a data url', function () {
    // `javascript:` is the one everybody blocks; `data:` is the one that
    // gets forgotten, and it is a script the browser will run.
    expect(Sanitizer::clean('<a href="data:text/html,<script>alert(1)</script>">x</a>'))
        ->not->toContain('data:');
});

it('drops a tag that can load something', function () {
    // No img, no iframe, no object. A rich-text field is words.
    //
    // Written with surrounding text on purpose. `clean('<img>')` alone
    // returns null whatever the allowlist says, because a document of
    // nothing but empty tags collapses to null — so that version of this
    // test passed with the allowlist removed entirely. Caught by
    // falsification and by nothing else.
    expect(Sanitizer::clean('<p>текст</p><img src=x onerror=alert(1)>'))
        ->toBe('<p>текст</p>');

    expect(Sanitizer::clean('<p>текст</p><iframe src="https://example.com"></iframe>'))
        ->toBe('<p>текст</p>');
});

it('removes styling that could cover the page', function () {
    // A `position: fixed` paragraph is a phishing overlay written by
    // somebody with nothing but a text field. `class` and `id` go too:
    // both let content reach into the application's own stylesheet.
    expect(Sanitizer::clean('<p style="position:fixed;top:0;left:0">поверх всего</p>'))
        ->toBe('<p>поверх всего</p>');

    expect(Sanitizer::clean('<p class="fixed inset-0" id="x">поверх всего</p>'))
        ->toBe('<p>поверх всего</p>');
});

it('keeps no heading big enough to impersonate the page', function () {
    // h1 belongs to the page, not to an author writing inside it.
    expect(Sanitizer::clean('<h1>Как настоящий заголовок</h1>'))
        ->not->toContain('<h1');
});

// ── Empty data ───────────────────────────────────────────────────────

it('turns an empty document into null', function () {
    // Tiptap sends `<p></p>` for an empty editor. Stored as-is it makes
    // an empty field look filled to every query asking whether it is
    // null.
    expect(Sanitizer::clean('<p></p>'))->toBeNull()
        ->and(Sanitizer::clean(''))->toBeNull()
        ->and(Sanitizer::clean('   '))->toBeNull();
});

it('passes null straight through', function () {
    expect(Sanitizer::clean(null))->toBeNull();
});
