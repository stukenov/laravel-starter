<?php

namespace App\Http\Controllers;

use App\Support\Locale\Locales;
use App\Support\Seo\HasSitemapEntries;
use Illuminate\Http\Response;

/**
 * RULE(i18n): every public page exists once per locale — AGENTS.md.
 *
 * Which means a crawler meets three addresses showing the same thing, and
 * without `hreflang` it has to guess which is canonical — usually wrongly.
 * Each entry therefore lists its siblings, itself included, plus
 * `x-default`.
 *
 * Only public routes belong here. Everything under `/dash` needs a session,
 * and advertising it would hand out 302s and a map of the panel.
 */
class SitemapController extends Controller
{
    /** @var array<int, array{name: string, priority: string}> */
    private const PAGES = [
        ['name' => 'home', 'priority' => '1.0'],
        ['name' => 'legal.privacy', 'priority' => '0.3'],
        ['name' => 'legal.terms', 'priority' => '0.3'],
    ];

    /**
     * Every model that implements HasSitemapEntries adds itself here.
     *
     * A content site's sitemap is mostly its content, and a controller
     * that only knows about static pages leaves the majority of the site
     * out — silently, which is the worst way for a sitemap to be wrong.
     *
     * @var array<int, class-string<HasSitemapEntries>>
     */
    private const MODELS = [];

    public function __invoke(): Response
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        foreach ($this->pages() as $page) {
            foreach (Locales::SUPPORTED as $locale) {
                $lines[] = '  <url>';
                $lines[] = '    <loc>'.e($this->url($page, $locale)).'</loc>';

                foreach (Locales::SUPPORTED as $alternate) {
                    $lines[] = sprintf(
                        '    <xhtml:link rel="alternate" hreflang="%s" href="%s"/>',
                        $alternate,
                        e($this->url($page, $alternate)),
                    );
                }

                $lines[] = sprintf(
                    '    <xhtml:link rel="alternate" hreflang="x-default" href="%s"/>',
                    e($this->url($page, Locales::FALLBACK)),
                );

                $lines[] = '    <priority>'.$page['priority'].'</priority>';
                $lines[] = '  </url>';
            }
        }

        $lines[] = '</urlset>';

        return response(implode("\n", $lines), 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * The static pages plus whatever the models contribute.
     *
     * @return array<int, array{name: string, priority: string, parameters?: array<string, mixed>}>
     */
    private function pages(): array
    {
        $pages = self::PAGES;

        foreach (self::MODELS as $model) {
            foreach ($model::sitemapEntries() as $entry) {
                $pages[] = [
                    'name' => $entry['route'],
                    'priority' => $entry['priority'] ?? '0.6',
                    'parameters' => $entry['parameters'],
                ];
            }
        }

        return $pages;
    }

    /**
     * @param  array{name: string, priority: string, parameters?: array<string, mixed>}  $page
     */
    private function url(array $page, string $locale): string
    {
        return route($page['name'], array_merge(
            $page['parameters'] ?? [],
            ['locale' => $locale],
        ));
    }
}
