<?php

declare(strict_types=1);

return [

    /*
     * RULE(i18n): every public page exists once per locale — AGENTS.md.
     *
     * Models that contribute addresses to `sitemap.xml`. Each implements
     * `App\Support\Seo\HasSitemapEntries` and returns route names with
     * their parameters; the locale and the alternates are added for it.
     *
     * Empty here on purpose — the starter ships no public content. A
     * content site adds its models and its sitemap stops being three
     * static pages while the site is a thousand.
     */
    'sitemap_models' => [
        // App\Models\Article::class,
    ],

];
