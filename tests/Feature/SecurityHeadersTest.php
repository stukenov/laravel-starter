<?php

// Correct data
it('sends a content security policy', function () {
    $this->get('/ru')->assertHeader('Content-Security-Policy');
});

it('restricts everything to our own origin by default', function () {
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("base-uri 'self'")
        ->and($csp)->toContain("form-action 'self'")
        ->and($csp)->toContain("frame-ancestors 'none'");
});

it('sends the hardening headers', function () {
    $response = $this->get('/ru');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy');
});

// Wrong data
it('allows no external host at all', function () {
    // Смысл всей затеи: запрет исполняет браузер, а не договорённость.
    // Появился здесь чужой домен — значит правило обошли.
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    expect($csp)->not->toContain('http://')
        ->and($csp)->not->toContain('https://')
        ->and($csp)->not->toContain('*');
});

it('does not allow inline scripts', function () {
    // Для стилей unsafe-inline допустим — Laravel отдаёт шрифт встроенным
    // блоком. Для скриптов это дыра, там только nonce.
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    preg_match('/script-src ([^;]+)/', $csp, $matches);

    expect($matches[1] ?? '')->toContain('nonce-')
        ->and($matches[1] ?? '')->not->toContain('unsafe-inline')
        ->and($matches[1] ?? '')->not->toContain('unsafe-eval');
});

it('lets our own bundles load alongside the nonce', function () {
    // Найдено проверкой в браузере, а не тестом: с одним лишь nonce
    // блокируется весь JavaScript приложения — у тегов <script src>,
    // которые ставит Vite, nonce нет. Страница при этом отдаётся со
    // статусом 200 и с разметкой, поэтому обычный тест ничего не заметит.
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    preg_match('/script-src ([^;]+)/', $csp, $matches);

    expect($matches[1] ?? '')->toContain("'self'");
});

it('generates a nonce of usable length', function () {
    // Уникальность nonce между запросами здесь не проверить: генератор
    // кэширует значение в пределах процесса, а весь набор тестов идёт
    // в одном. На настоящих HTTP-запросах значения разные — проверено
    // двумя curl подряд. Здесь проверяем то, что проверить можно:
    // nonce есть и достаточно длинный, чтобы его нельзя было угадать.
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    preg_match("/nonce-([A-Za-z0-9+\/=]+)/", $csp, $matches);

    expect($matches[1] ?? '')->toHaveLength(32);
});

// Empty data
it('still sends the policy on an error page', function () {
    $this->get('/ru/no-such-page')
        ->assertNotFound()
        ->assertHeader('Content-Security-Policy');
});

it('sends no hsts header over plain http', function () {
    // На http он бессмыслен, а в разработке заставил бы браузер намертво
    // запомнить редирект на https.
    expect($this->get('/ru')->headers->get('Strict-Transport-Security'))->toBeNull();
});

/**
 * The session cookie carries the identity of whoever is signed in, so its
 * flags matter as much as any header.
 *
 * `SESSION_SECURE_COOKIE` is unset and `config('session.secure')` is
 * therefore null, which reads like an oversight and is not: null means
 * "follow the request", so the cookie is marked Secure over https and
 * left alone over http, where marking it would stop development working
 * at all. That was checked rather than assumed, and pinned here because
 * it rests on framework behaviour nothing else states.
 *
 * Behind a TLS-terminating proxy the request looks plaintext to PHP
 * unless `TRUSTED_PROXIES` is set — the same setting that governs HSTS
 * and the login limiter. See the Operations section of AGENTS.md.
 */
function sessionCookie($response)
{
    foreach ($response->headers->getCookies() as $cookie) {
        if ($cookie->getName() === config('session.cookie')) {
            return $cookie;
        }
    }

    return null;
}

// ── Correct data ─────────────────────────────────────────────────────

it('marks the session cookie secure over https', function () {
    $cookie = sessionCookie($this->get('https://localhost/ru'));

    expect($cookie)->not->toBeNull()
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('leaves the session cookie usable over plain http', function () {
    // Marking it Secure unconditionally would sign nobody in on a
    // development machine, and the failure looks like a broken login
    // rather than a cookie that never came back.
    $cookie = sessionCookie($this->get('http://localhost/ru'));

    expect($cookie)->not->toBeNull()
        ->and($cookie->isSecure())->toBeFalse()
        ->and($cookie->isHttpOnly())->toBeTrue();
});

// ── Empty data ───────────────────────────────────────────────────────

it('sets a session cookie at all', function () {
    // Both assertions above pass on a response that sets no cookie.
    expect(sessionCookie($this->get('https://localhost/ru')))->not->toBeNull();
});
