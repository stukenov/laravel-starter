import { resolve } from 'node:path';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

/**
 * ПРАВИЛО(тесты): три уровня, это второй — отрисовка — AGENTS.md.
 *
 * Отдельный конфиг, а не общий с vite.config.ts: там подключены плагины
 * Laravel, Inertia и Wayfinder, которым в тестовой среде нечего делать —
 * они ждут работающего бэкенда и манифеста сборки.
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
