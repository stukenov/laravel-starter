<?php

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

];
