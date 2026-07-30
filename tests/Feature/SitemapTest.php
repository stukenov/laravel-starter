<?php

declare(strict_types=1);

use App\Support\Locale\Locales;
use App\Support\Seo\HasSitemapEntries;

// ── Correct data ─────────────────────────────────────────────────────

it('lists every public page in every language', function () {
    $body = $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('content-type', 'application/xml')
        ->getContent();

    foreach (Locales::SUPPORTED as $locale) {
        expect($body)->toContain('<loc>'.config('app.url')."/{$locale}</loc>")
            ->and($body)->toContain("/{$locale}/privacy")
            ->and($body)->toContain("/{$locale}/terms");
    }
});

it('tells a crawler which addresses are the same page', function () {
    // Three URLs show the same thing. Without hreflang a crawler picks a
    // canonical one itself, and usually the wrong one.
    $body = $this->get('/sitemap.xml')->getContent();

    foreach (Locales::SUPPORTED as $locale) {
        expect($body)->toContain('hreflang="'.$locale.'"');
    }

    expect($body)->toContain('hreflang="x-default"');
});

it('is well-formed xml', function () {
    $xml = simplexml_load_string($this->get('/sitemap.xml')->getContent());

    expect($xml)->not->toBeFalse()
        ->and(count($xml->url))->toBe(3 * count(Locales::SUPPORTED));
});

// ── Wrong data ───────────────────────────────────────────────────────

it('keeps the panel out of the sitemap', function () {
    // Every one of those needs a session. Advertising them hands a
    // crawler a pile of redirects and hands everyone else a map.
    $body = $this->get('/sitemap.xml')->getContent();

    expect($body)->not->toContain('/dash')
        ->and($body)->not->toContain('/settings')
        ->and($body)->not->toContain('/login');
});

it('asks robots to stay away outside production', function () {
    // A staging copy in the index is a real problem and an awkward one to
    // undo, so anything that is not production refuses everything.
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /')
        ->assertDontSee('Sitemap:');
});

it('is not shadowed by a file in public', function () {
    // A web server answers with the file and never reaches the route, so
    // the dynamic version would silently stop being used.
    expect(file_exists(public_path('robots.txt')))->toBeFalse()
        ->and(file_exists(public_path('sitemap.xml')))->toBeFalse();
});

// ── Empty data ───────────────────────────────────────────────────────

it('needs no session and no data', function () {
    // The sitemap is fetched by strangers, before anything exists.
    $this->assertGuest();

    $this->get('/sitemap.xml')->assertOk();
    $this->get('/robots.txt')->assertOk();
});

it('lets a model add its own pages', function () {
    // A content site's sitemap is mostly its content. A controller that
    // knows only about static pages leaves out the majority of the site,
    // and does it silently — the worst way for a sitemap to be wrong.
    $interface = HasSitemapEntries::class;

    expect(interface_exists($interface))->toBeTrue()
        ->and(method_exists($interface, 'sitemapEntries'))->toBeTrue();
});

it('builds a model page in every language', function () {
    // The same hreflang treatment the static pages get. A detail page
    // that exists in three languages and declares one is worse than a
    // page with no declaration at all.
    $body = $this->get('/sitemap.xml')->getContent();

    $locales = count(Locales::SUPPORTED);

    // Each page appears once per locale, and every one of those lists
    // every locale as an alternate — so a given hreflang shows up
    // pages × locales times, not once.
    $pages = (int) (substr_count($body, '<loc>') / $locales);

    foreach (Locales::SUPPORTED as $locale) {
        expect(substr_count($body, 'hreflang="'.$locale.'"'))
            ->toBe($pages * $locales, "alternates for {$locale}");
    }
});
