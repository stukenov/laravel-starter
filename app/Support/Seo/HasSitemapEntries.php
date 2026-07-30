<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * RULE(i18n): every public page exists once per locale — AGENTS.md.
 *
 * A model-backed public section implements this and appears in the
 * sitemap without anyone editing the controller. Without it the sitemap
 * lists the three static pages and nothing else, which for a content site
 * is the majority of the site missing — and nothing anywhere says so.
 */
interface HasSitemapEntries
{
    /**
     * The public addresses this model contributes, one per record.
     *
     * Return route names and parameters, not URLs: the locale is added
     * for each supported language, and the alternates with it.
     *
     * @return iterable<int, array{route: string, parameters: array<string, mixed>, priority?: string}>
     */
    public static function sitemapEntries(): iterable;
}
