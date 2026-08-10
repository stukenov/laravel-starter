<?php

declare(strict_types=1);

return [

    /*
     * RULE(security): who may say where a request came from — AGENTS.md.
     *
     * Empty under the nginx→PHP-FPM deployment in README: nginx already
     * passes the real client address and marks the request secure, so
     * `$request->ip()` and `$request->secure()` are right without help.
     *
     * Behind a CDN or a load balancer they are both wrong at once, and
     * silently: every visitor shares one address, so the login limiter
     * becomes one bucket for the internet, the sessions page shows that
     * address for everybody, and HSTS stops being sent because the
     * request no longer looks secure.
     *
     * Set it to the balancer's addresses, or to `*` when they are not
     * knowable and nothing can reach the app except through it. Trusting
     * `*` on an app that is reachable directly lets anyone forge their
     * own IP.
     */
    'trusted_proxies' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))),
    )),

    /*
     * RULE(security): a honeypot instead of a captcha — AGENTS.md.
     *
     * Every public form that writes something belongs here, by route
     * name. The list existed as one hard-coded string, which was right
     * for a starter with exactly one public form and wrong the moment a
     * project added a second — the new form would be unprotected, and
     * nothing would say so. A spammer finds it before anybody else does.
     *
     * Sign-in is deliberately absent. A honeypot field on a login form
     * is a nuisance to password managers and buys nothing: what protects
     * that route is the rate limiter, five attempts a minute per email
     * and address.
     */
    /*
     * Conditional, the same way `fortify.features` is: naming a route that
     * does not exist is exactly the typo this list is checked for, and
     * `register.store` legitimately disappears when registration is closed.
     *
     * `env()` rather than Fortify's Features here — a config file loads
     * before the facades have a root, which is why config/fortify.php
     * narrows by hand too.
     */
    'spam_protected_routes' => array_values(array_filter([
        env('REGISTRATION_ENABLED', true) ? 'register.store' : null,
        // Registration can be switched off; this form cannot. Left out, a
        // starter with REGISTRATION_ENABLED=false had no honeypot anywhere
        // and the canary in SpamProtectionTest had nothing to check.
        'password.email',
    ])),

];
