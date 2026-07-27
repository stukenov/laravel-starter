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
