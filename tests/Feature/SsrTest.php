<?php

use Illuminate\Support\Facades\Http;

/** Whether anything is listening on the SSR port at all. */
function ssrIsReachable(): bool
{
    $url = parse_url((string) config('inertia.ssr.url'));
    $socket = @fsockopen($url['host'] ?? '127.0.0.1', $url['port'] ?? 13714, $errno, $error, 1);

    if ($socket === false) {
        return false;
    }

    fclose($socket);

    return true;
}

// ── Correct data ─────────────────────────────────────────────────────

it('enables server side rendering', function () {
    expect(config('inertia.ssr.enabled'))->toBeTrue();
});

it('points at a bundle that the build actually produces', function () {
    // The name comes from the @inertiajs/vite plugin: entry app.tsx →
    // app.js. Pointed at a file that does not exist, production serves an
    // empty container instead of markup and says nothing about it.
    expect(config('inertia.ssr.bundle'))->toBe(base_path('bootstrap/ssr/app.js'));
});

it('has a built bundle on disk', function () {
    expect(config('inertia.ssr.bundle'))->toBeFile();
})->skip(
    fn () => ! file_exists(base_path('bootstrap/ssr/app.js')),
    'bundle not built: npm run build:ssr',
);

it('is answered by this bundle and not another project\'s', function () {
    // The port is 13714 for every Inertia application, and the number is
    // baked into the bundle by the plugin — it cannot be changed per
    // project. So two of them on one machine share a renderer: whichever
    // started first answers for both, and the other silently falls back
    // to an empty container.
    //
    // This asks the running server to render a page only this project has.
    $response = Http::timeout(5)->post(config('inertia.ssr.url').'/render', [
        'component' => 'home',
        'props' => [
            'name' => 'SSR-PROBE-MARKER',
            'locale' => 'ru',
            'locales' => ['ru', 'kk', 'en'],
            'auth' => ['user' => null],
            'translations' => [],
        ],
        'url' => '/ru',
        'version' => null,
        'clearHistory' => false,
        'encryptHistory' => false,
    ]);

    expect($response->json('body'))->toContain('SSR-PROBE-MARKER');
})->skip(
    fn () => ! ssrIsReachable(),
    'no SSR server running: php artisan inertia:start-ssr',
);

// ── Wrong data ───────────────────────────────────────────────────────

it('does not rely on a hand written ssr entry point', function () {
    // In Inertia 3 the plugin wraps the entry point in the server
    // bootstrap itself. An ssr.tsx means somebody brought the Inertia 2
    // approach along.
    expect(resource_path('js/ssr.tsx'))->not->toBeFile()
        ->and(resource_path('js/ssr.ts'))->not->toBeFile();
});

// ── Empty data ───────────────────────────────────────────────────────

it('has an ssr url to fall back on', function () {
    expect(config('inertia.ssr.url'))->not->toBeEmpty();
});
