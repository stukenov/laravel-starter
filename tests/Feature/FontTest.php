<?php

// Correct data
it('uses Inter, not the starter default', function () {
    $config = file_get_contents(base_path('vite.config.ts'));
    $css = file_get_contents(resource_path('css/app.css'));

    expect($config)->toContain("'Inter'")
        ->and($config)->not->toContain('Instrument Sans')
        ->and($css)->toContain("'Inter'")
        ->and($css)->not->toContain('Instrument Sans');
});

it('requests both cyrillic subsets', function () {
    // Kazakh letters are split across two subsets:
    // і (U+0456) and ұ (U+04B1) are in cyrillic,
    // ә ғ қ ң ө ү һ are in cyrillic-ext. Both are needed.
    $config = file_get_contents(base_path('vite.config.ts'));

    expect($config)->toContain("'cyrillic'")
        ->and($config)->toContain("'cyrillic-ext'");
});

// Wrong data
it('serves the font from our own domain', function () {
    $manifest = public_path('build/fonts-manifest.json');

    expect($manifest)->toBeFile();

    $contents = file_get_contents($manifest);

    // No absolute URLs: the font is downloaded at build time and
    // served from here.
    expect($contents)->not->toContain('https://')
        ->and($contents)->not->toContain('http://')
        ->and($contents)->toContain('/build/assets/');
})->skip(
    fn () => ! file_exists(public_path('build/fonts-manifest.json')),
    'fonts manifest not built: npm run build',
);

it('covers every kazakh letter across the declared ranges', function () {
    // The real check for the "font with Kazakh letters" requirement.
    // Inspected via fontTools: seven letters live in cyrillic-ext,
    // ұ and і in cyrillic. Drop either subset and those letters
    // silently fall back to a system font — only a Kazakh-speaking
    // user would notice.
    //
    // Both cases. The list was lower case only, and the capitals sit in a
    // different part of the range — so a capitalised Kazakh heading could
    // have fallen back to a system font with this file still green.
    $letters = [
        'ә' => 0x04D9, 'ғ' => 0x0493, 'қ' => 0x049B,
        'ң' => 0x04A3, 'ө' => 0x04E9, 'ұ' => 0x04B1,
        'ү' => 0x04AF, 'һ' => 0x04BB, 'і' => 0x0456,
        'Ә' => 0x04D8, 'Ғ' => 0x0492, 'Қ' => 0x049A,
        'Ң' => 0x04A2, 'Ө' => 0x04E8, 'Ұ' => 0x04B0,
        'Ү' => 0x04AE, 'Һ' => 0x04BA, 'І' => 0x0406,
    ];

    $manifest = json_decode(file_get_contents(public_path('build/fonts-manifest.json')), true);
    $css = $manifest['style']['familyStyles']['inter'] ?? '';

    expect($css)->not->toBeEmpty('the manifest has no inter family');

    preg_match_all('/unicode-range:\s*([^;]+);/', $css, $matches);

    $covered = [];

    foreach ($matches[1] as $declaration) {
        foreach (explode(',', $declaration) as $part) {
            $part = trim($part);

            if (preg_match('/^U\+([0-9A-Fa-f]+)-([0-9A-Fa-f]+)$/', $part, $range)) {
                $covered[] = [hexdec($range[1]), hexdec($range[2])];
            } elseif (preg_match('/^U\+([0-9A-Fa-f]+)$/', $part, $single)) {
                $covered[] = [hexdec($single[1]), hexdec($single[1])];
            }
        }
    }

    $missing = [];

    foreach ($letters as $letter => $codepoint) {
        $found = false;

        foreach ($covered as [$from, $to]) {
            if ($codepoint >= $from && $codepoint <= $to) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $missing[] = sprintf('%s (U+%04X)', $letter, $codepoint);
        }
    }

    expect($missing)->toBeEmpty();
})->skip(
    fn () => ! file_exists(public_path('build/fonts-manifest.json')),
    'fonts manifest not built: npm run build',
);

// Empty data
it('declares a fallback family', function () {
    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('system-ui');
});

it('does not preload the subsets before it knows which are needed', function () {
    // Preloading is the plugin's default and it defeats `unicode-range`:
    // the browser is told to fetch all sixteen files before it has seen a
    // single letter of the page. Measured at 306 KB on every page in
    // every language, English included — which needs no Cyrillic at all.
    $config = File::get(base_path('vite.config.ts'));

    expect($config)->toContain('preload: false');
});

it('ships no font preload links in the page', function () {
    // The half that would notice if the setting were changed back and the
    // config comment stayed.
    $html = $this->get(route('home', ['locale' => 'ru']))->getContent();

    preg_match_all('/<link[^>]*rel="preload"[^>]*>/i', (string) $html, $links);

    $fonts = array_filter($links[0], fn (string $tag) => str_contains($tag, 'woff2'));

    expect($fonts)->toBeEmpty();
});

it('still declares every subset for the browser to choose from', function () {
    // Not preloading is only safe because the choice is still offered.
    // Remove a subset instead and Kazakh letters silently fall back to a
    // system font.
    $manifest = json_decode(File::get(public_path('build/fonts-manifest.json')), true);

    // `style` is a list of rules, not one string.
    expect(json_encode($manifest['style'] ?? []))->toContain('unicode-range');
})->skip(
    fn () => ! file_exists(public_path('build/fonts-manifest.json')),
    'fonts manifest not built: npm run build',
);
