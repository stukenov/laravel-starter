import { resolve } from 'node:path';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

/**
 * RULE(tests): three levels, and this is the second — rendering.
 * See AGENTS.md.
 *
 * A config of its own rather than sharing vite.config.ts: that one loads
 * the Laravel, Inertia and Wayfinder plugins, which have no business in a
 * test environment — they expect a running backend and a build manifest.
 */
export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        include: ['tests/js/**/*.test.{ts,tsx}'],
        setupFiles: ['tests/js/setup.ts'],
    },
});
