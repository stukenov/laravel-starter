<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * RULE(external): no foreign domain at render time — AGENTS.md.
 *
 * Not documentation but an executable ban: the browser itself rejects a
 * request to an outside host. If somebody adds an analytics widget or a
 * CDN font, the page breaks loudly instead of leaking quietly.
 *
 * It follows that reCAPTCHA and friends are impossible here by
 * construction — honeypot instead.
 */
class AppPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::OBJECT, Keyword::NONE)
            // data: covers inlined images and fonts emitted by Vite.
            // Not an external resource — the bytes are in the document.
            ->add(Directive::IMG, [Keyword::SELF, 'data:'])
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            // Inline styles are unavoidable: Laravel serves the font as
            // an inline block and Tailwind injects styles in dev. The
            // same leniency is unacceptable for scripts — nonce there.
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            // 'self' must sit next to the nonce, and that is not
            // obvious. A nonce alone blocks our own Vite bundles: their
            // <script src> tags carry none. The page still returns 200
            // with valid markup — only every script dies, and you can
            // only see that in a browser.
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT);
    }
}
