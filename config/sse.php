<?php

return [

    /*
     * RULE(sse): an open connection holds a worker — AGENTS.md.
     *
     * The two numbers that decide how many people can watch the activity
     * feed at once. A connection occupies an FPM worker for its whole
     * life; the browser then waits `retry_seconds` and opens another.
     *
     * One viewer costs, on average:
     *
     *     lifetime / (lifetime + retry)   workers
     *
     * At the defaults that is 25 / 28, or about 0.9 — near enough to one
     * worker each, permanently. With `pm.max_children = 30` that is
     * roughly 27 simultaneous viewers before the rest of the site starts
     * queueing behind them.
     *
     * Raising `retry_seconds` is the cheap lever: at 15 seconds a viewer
     * costs 0.63 workers and the feed lags by up to fifteen seconds after
     * a reconnect. Whether that trade is right depends on whether anyone
     * is actually watching the feed live.
     *
     * The other answer is isolation — a separate FPM pool for the stream
     * route, so a saturated feed cannot take the panel down with it. See
     * README, "Deploying".
     */
    'lifetime_seconds' => (int) env('SSE_LIFETIME_SECONDS', 25),

    'retry_seconds' => (int) env('SSE_RETRY_SECONDS', 3),

];
