import '@testing-library/jest-dom/vitest';
import { cleanup, configure } from '@testing-library/react';
import { afterEach } from 'vitest';

/*
 * RULE(tests): the element marker is data-test, not data-testid.
 *
 * The whole starter is marked up this way, and Playwright uses the
 * same attribute. Keeping two conventions in one project is a sure
 * way to later spend half a day figuring out why an element "can't
 * be found".
 * See AGENTS.md
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

// jsdom has no ResizeObserver either, and Radix uses it to place a
// popover. The sign-in page pulls one in through the password field, so
// a test that never opens a menu still dies without this.
globalThis.ResizeObserver ??= class {
    observe() {}
    unobserve() {}
    disconnect() {}
} as unknown as typeof ResizeObserver;
