<?php

use App\Models\User;
use App\Support\Locale\Locales;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Route::middleware('web')->group(function () {
        Route::get('/test-403', fn () => abort(403));
        Route::get('/test-500', fn () => abort(500));
        Route::post('/test-419', fn () => abort(419));
    });
});

// Correct data
it('renders a full inertia page for 404', function () {
    $this->get('/ru/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('errors/error-page')
            ->where('status', 404)
            ->where('title', 'Страница не найдена'),
        );
});

it('renders a full inertia page for 403', function () {
    $this->get('/test-403')
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('errors/error-page')
            ->where('status', 403),
        );
});

it('translates the error page into the active locale', function () {
    $user = User::factory()->create(['locale' => 'kk']);

    $this->actingAs($user)
        ->get('/test-403')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Қолжетімсіз'),
        );
});

it('keeps the http status code, not just the look of an error', function () {
    // An error page served with status 200 fools search engines and
    // monitoring: from the outside everything looks healthy.
    $this->get('/ru/no-such-page')
        ->assertStatus(404);
});

// Wrong data
it('redirects 419 to login instead of showing an error page', function () {
    // Locale is set explicitly: with no cookie or profile it comes
    // from the browser header, and the test client asks for English
    // by default.
    $this->withUnencryptedCookie(Locales::COOKIE, 'ru')
        ->post('/test-419')
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Сессия истекла. Войдите заново.');
});

it('leaves api requests alone', function () {
    // A request expecting JSON doesn't need an error page — it needs
    // JSON. Otherwise an API client gets HTML back and can't parse it.
    $this->getJson('/ru/no-such-page')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');
});

// Empty data
it('has a blade fallback for every handled status', function () {
    foreach ([403, 404, 419, 429, 500, 503] as $status) {
        expect(resource_path("views/errors/{$status}.blade.php"))->toBeFile();
    }
});

it('renders the blade fallback without any external request', function () {
    // These files work when nothing else does: SSR is down, no build
    // exists. Any external link or @vite breaks them exactly when
    // they're needed.
    foreach ([403, 404, 419, 429, 500, 503] as $status) {
        $blade = file_get_contents(resource_path("views/errors/{$status}.blade.php"));

        expect($blade)->not->toContain('http://')
            ->and($blade)->not->toContain('https://')
            ->and($blade)->not->toContain('@vite(');
    }
});

/*
 * RULE(errors): a page, not a modal — AGENTS.md.
 *
 * The handler used to normalise a list of five statuses and return
 * everything else untouched. A GET on a POST-only route is a 405, which
 * was not on the list, and both outcomes were bad: with `APP_DEBUG` on —
 * which is what `.env.example` ships — the visitor got a stack trace and
 * the vendor paths; with it off, plain HTML, which is exactly what makes
 * Inertia draw the grey modal this rule calls a bug.
 *
 * Both were reproduced before the fix. The list is gone: every status at
 * or above 400 is a page now, and the ones without wording of their own
 * get a generic line rather than a missing translation key on screen.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('draws a page for a status nobody wrote copy for', function () {
    $response = $this->get('/email/verification-notification');

    expect($response->status())->toBe(405)
        ->and($response->getContent())->toContain('errors\/error-page');
});

it('shows wording rather than a translation key', function () {
    $response = $this->get('/email/verification-notification');

    expect($response->getContent())->not->toContain('common.errors');
});

it('keeps the wording each documented status already had', function () {
    // The generic line is a fallback, not a replacement.
    $this->get('/definitely-not-a-route')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->where('title', trans('common.errors.404.title')),
        );
});

// ── Wrong data ───────────────────────────────────────────────────────

it('leaks no stack trace even with debug on', function () {
    // `.env.example` ships APP_DEBUG=true, so this is the default state
    // of a fresh clone, not an unusual one.
    config(['app.debug' => true]);

    $body = $this->get('/email/verification-notification')->getContent();

    expect($body)->not->toContain('/vendor/laravel/framework')
        ->and(preg_match('/#0 \/Users/', $body))->toBe(0);
});

// ── Empty data ───────────────────────────────────────────────────────

it('leaves a successful response alone', function () {
    // The handler must not wrap anything below 400 — a redirect is not an
    // error, and neither is a 200.
    $this->get('/ru')->assertOk();
});
