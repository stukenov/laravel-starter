import { defineConfig, devices } from '@playwright/test';

/**
 * RULE(tests): three levels — Pest, Vitest, Playwright. See AGENTS.md.
 *
 * This level exists to catch what the other two cannot: a page that
 * returns 200 with valid markup and is still broken. CSP blocking every
 * script, an SSR bundle rendering something the client then disagrees
 * with, a stream that never opens — all of it passes a backend test.
 *
 * So the run is deliberately production-shaped: built assets, the SSR
 * process alive, no Vite dev server.
 */
const PORT = 8001;
const DATABASE = 'laravel_starter_e2e';

export default defineConfig({
    testDir: './tests/e2e',
    // One database, shared by every spec. Parallel workers would race on
    // it, and a starter is better off slow and honest than fast and
    // flaky.
    fullyParallel: false,
    workers: 1,
    reporter: process.env.CI ? 'list' : [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        // The project marks elements with data-test, and Playwright looks
        // for data-testid unless told otherwise.
        testIdAttribute: 'data-test',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
    webServer: {
        // One command on purpose: the build has to finish before the SSR
        // process reads the bundle, and the database has to be ready
        // before the first request. Two webServer entries would start in
        // parallel and win that race about half the time.
        //
        // PHP's built-in server is single threaded, and one open SSE
        // connection would otherwise hold every other request for its
        // full 25-second lifetime.
        command: [
            'npm run build:ssr',
            'php artisan e2e:prepare',
            `(php artisan inertia:start-ssr & php artisan serve --host=127.0.0.1 --port=${PORT})`,
        ].join(' && '),
        url: `http://127.0.0.1:${PORT}/ru`,
        reuseExistingServer: false,
        timeout: 180_000,
        stdout: 'pipe',
        stderr: 'pipe',
        env: {
            APP_ENV: 'local',
            DB_DATABASE: DATABASE,
            PHP_CLI_SERVER_WORKERS: '6',
        },
    },
});
