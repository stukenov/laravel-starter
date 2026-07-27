import '@testing-library/jest-dom/vitest';
import { cleanup, configure } from '@testing-library/react';
import { afterEach } from 'vitest';

/*
 * ПРАВИЛО(тесты): маркер элементов — data-test, а не data-testid.
 *
 * Так размечен стартер целиком, и тем же атрибутом пользуется Playwright.
 * Держать два соглашения в одном проекте — верный способ потом полдня
 * искать, почему элемент «не находится».
 * См. AGENTS.md
 */
configure({ testIdAttribute: 'data-test' });

afterEach(() => {
    cleanup();
});

// jsdom has no matchMedia, and the sidebar asks it whether the screen is
// a phone. Without this any test that renders the panel chrome dies
// before its first assertion.
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: (query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addEventListener: () => {},
        removeEventListener: () => {},
        addListener: () => {},
        removeListener: () => {},
        dispatchEvent: () => false,
    }),
});
