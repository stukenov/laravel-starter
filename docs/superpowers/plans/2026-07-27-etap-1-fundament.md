# Этап 1 — фундамент. План реализации

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Поднять работающую основу стартера: Laravel 13 на PostgreSQL 18 с рабочим Inertia SSR, локальным шрифтом с кириллицей, тремя локалями, временем в UTC, страницами ошибок вместо модального окна и заголовками безопасности.

**Architecture:** Стартуем с официального React-стартера Laravel (`laravel new --react`), потому что заказчик просил переписать стандартные страницы, а не изобретать Inertia заново. Авторизацию оставляем на Fortify — он безвидовый, все экраны всё равно наши. Дальше вычищаем демонстрационное содержимое, доводим базу до Postgres-специфичного вида и добавляем то, чего в стартере нет: SSR, локали, таймзоны, страницы ошибок, CSP.

**Tech Stack:** Laravel 13.22, Fortify 1.37, Inertia 3.1 (SSR), React 19.2, TypeScript, Tailwind 4.3, shadcn/ui, PostgreSQL 18.4, Pest 4.7, Vitest, Playwright.

## Global Constraints

Эти требования действуют в каждой задаче без повторного упоминания.

- **База только PostgreSQL.** Драйвер `pgsql`. Никакого SQLite даже в тестах.
- **Первичные ключи** — `uuid` со значением по умолчанию `uuidv7()` на уровне базы.
- **Все временные колонки** — `timestamptz`. Приложение работает в `UTC`.
- **Ни одного внешнего домена при отрисовке страницы.** Проверяется заголовками CSP, а не договорённостью.
- **Только светлая тема.** Класс `dark`, переключатель и страница `settings/appearance` удаляются полностью.
- **Один шрифт** — Inter, локальный, с поддержкой девяти казахских букв: ә ғ қ ң ө ұ ү һ і.
- **Три локали:** `ru`, `kk`, `en`. Локаль по умолчанию — `ru`.
- **Покрытие тестами:** каждая фича — три уровня (Pest, Vitest, Playwright) × три состояния данных (правильные, неправильные, пустые). Написанный, но не запущенный тест не считается написанным.
- **Язык кода** — английский (имена, комментарии). Язык интерфейса и карточек `zettel/` — русский.
- **Каждая задача заканчивается коммитом.** После каждой задачи — новая карточка в `zettel/`, если появилась мысль, которой там ещё нет.

---

## Структура файлов

Что создаётся и за что отвечает.

| Файл | Ответственность |
|---|---|
| `app/Providers/AppServiceProvider.php` | Защита от чужого драйвера БД |
| `resources/js/ssr.tsx` | Точка входа SSR |
| `vite.config.ts` | Сборка: вход клиента и SSR, шрифт, wayfinder |
| `config/inertia.php` | Путь до собранного SSR-бандла |
| `app/Http/Middleware/HandleInertiaRequests.php` | Общие свойства: локаль, таймзона, переводы |
| `app/Http/Middleware/SetLocale.php` | Определение локали из адреса или профиля |
| `app/Http/Middleware/SecurityHeaders.php` | HSTS, X-Frame-Options и прочие заголовки |
| `bootstrap/app.php` | Регистрация middleware и обработчик исключений |
| `resources/js/pages/errors/error-page.tsx` | Страница ошибки (Inertia) |
| `resources/views/errors/{404,419,500,503}.blade.php` | Запасной вариант, когда SSR мёртв |
| `resources/js/lib/datetime.ts` | Форматирование времени в зоне пользователя |
| `resources/js/lib/i18n.ts` | Хелпер `t()` |
| `lang/{ru,kk,en}/*.php` | Переводы |
| `database/migrations/*` | UUIDv7, citext, timestamptz |
| `tests/Feature/*`, `tests/js/*`, `tests/e2e/*` | Три уровня тестов |
| `AGENTS.md` | Правила проекта для ИИ-инструментов — единственный источник правды |
| `CLAUDE.md`, `.cursor/rules/project.mdc` | Ссылки на `AGENTS.md`, чтобы правила не разъезжались |
| `zettel/README.md` | Как вести картотеку |

---

## Task 1: Проект на месте, база подключена, тесты идут

Репозиторий уже содержит `docs/` и `zettel/`, а `laravel new` требует пустую папку. Поэтому ставим во временную директорию и переносим файлы поверх.

**Files:**
- Create: весь скелет Laravel в корне репозитория
- Modify: `.env`, `.gitignore`
- Test: `tests/Feature/SmokeTest.php`

**Interfaces:**
- Produces: рабочее приложение по адресу `http://127.0.0.1:8000`, база `laravel_starter`, команда `php artisan test`

- [ ] **Step 1: Создать базу данных**

```bash
export PATH="/Applications/Postgres.app/Contents/Versions/latest/bin:$PATH"
createdb -h 127.0.0.1 -U sakentukenov laravel_starter
createdb -h 127.0.0.1 -U sakentukenov laravel_starter_test
psql -h 127.0.0.1 -U sakentukenov -d laravel_starter -c "SELECT uuidv7();"
```

Ожидаем: выводится значение UUID. Если команда не найдена — путь к бинарникам Postgres.app указан выше, в системном `PATH` их нет.

- [ ] **Step 2: Установить Laravel во временную папку и перенести**

```bash
cd /tmp
rm -rf ls-install
~/.composer/vendor/bin/laravel new ls-install --react --pest --database=pgsql --no-interaction
cd /Users/sakentukenov/laravel-starter
rsync -a --exclude='.git' /tmp/ls-install/ ./
rm -rf /tmp/ls-install
```

`rsync` без `--delete` — он добавляет файлы Laravel, не трогая наши `docs/` и `zettel/`.

- [ ] **Step 3: Настроить подключение к базе**

В `.env` заменить блок базы данных:

```dotenv
APP_NAME="Laravel Starter"
APP_TIMEZONE=UTC
APP_LOCALE=ru
APP_FALLBACK_LOCALE=en

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_starter
DB_USERNAME=sakentukenov
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@laravel-starter.test"

REGISTRATION_ENABLED=true
```

В `phpunit.xml` заменить тестовую базу — по умолчанию там SQLite, а нам нельзя:

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="laravel_starter_test"/>
```

Убедиться, что строк `<env name="DB_CONNECTION" value="sqlite"/>` и `<env name="DB_DATABASE" value=":memory:"/>` не осталось.

- [ ] **Step 4: Прогнать миграции**

```bash
php artisan migrate --force
php artisan migrate --database=pgsql --force --env=testing
```

Ожидаем: таблицы созданы без ошибок.

- [ ] **Step 5: Написать дымовой тест**

`tests/Feature/SmokeTest.php`:

```php
<?php

use Illuminate\Support\Facades\DB;

it('runs on postgresql', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

it('has native uuidv7 available', function () {
    $value = DB::selectOne('select uuidv7() as id')->id;

    expect($value)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-/');
});

it('serves the home page', function () {
    $this->get('/')->assertOk();
});
```

- [ ] **Step 6: Запустить тесты**

```bash
php artisan test --filter=SmokeTest
```

Ожидаем: 3 passed. Если `uuidv7()` не найдена — версия Postgres ниже 18, план дальше невалиден.

- [ ] **Step 7: Установить Mailpit и проверить**

```bash
brew install mailpit
brew services start mailpit
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8025
```

Ожидаем: `200`.

- [ ] **Step 8: Коммит**

```bash
git add -A
git commit -m "feat: Laravel 13 на PostgreSQL 18, дымовые тесты проходят"
```

---

## Task 2: Приложение отказывается работать не на PostgreSQL

Спека требует, чтобы никто не смог случайно запустить проект на SQLite и поймать тихие расхождения.

**Files:**
- Create: `app/Exceptions/UnsupportedDatabaseDriver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/DatabaseDriverGuardTest.php`

**Interfaces:**
- Produces: `UnsupportedDatabaseDriver::forDriver(string $driver): self`, проверка в `AppServiceProvider::boot()`

- [ ] **Step 1: Написать падающий тест**

`tests/Feature/DatabaseDriverGuardTest.php`:

```php
<?php

use App\Exceptions\UnsupportedDatabaseDriver;
use App\Providers\AppServiceProvider;

it('accepts pgsql', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver('pgsql'))
        ->not->toThrow(UnsupportedDatabaseDriver::class);
});

it('rejects sqlite with a helpful message', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver('sqlite'))
        ->toThrow(UnsupportedDatabaseDriver::class, 'sqlite');
});

it('rejects an empty driver', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver(''))
        ->toThrow(UnsupportedDatabaseDriver::class);
});
```

- [ ] **Step 2: Запустить и убедиться, что падает**

```bash
php artisan test --filter=DatabaseDriverGuardTest
```

Ожидаем: FAIL — `Class "App\Exceptions\UnsupportedDatabaseDriver" not found`.

- [ ] **Step 3: Реализовать**

`app/Exceptions/UnsupportedDatabaseDriver.php`:

```php
<?php

namespace App\Exceptions;

use RuntimeException;

class UnsupportedDatabaseDriver extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'This application requires PostgreSQL. Configured driver: "%s". '.
            'Set DB_CONNECTION=pgsql in your .env file.',
            $driver === '' ? '(empty)' : $driver,
        ));
    }
}
```

В `app/Providers/AppServiceProvider.php` добавить метод и вызов в `boot()`:

```php
use App\Exceptions\UnsupportedDatabaseDriver;
use Illuminate\Support\Facades\DB;

public static function assertSupportedDriver(string $driver): void
{
    if ($driver !== 'pgsql') {
        throw UnsupportedDatabaseDriver::forDriver($driver);
    }
}

public function boot(): void
{
    self::assertSupportedDriver(DB::connection()->getDriverName());
}
```

- [ ] **Step 4: Запустить тесты**

```bash
php artisan test --filter=DatabaseDriverGuardTest
```

Ожидаем: 3 passed.

- [ ] **Step 5: Проверить вручную, что защита срабатывает**

```bash
DB_CONNECTION=sqlite php artisan about 2>&1 | head -5
```

Ожидаем: сообщение `This application requires PostgreSQL`.

- [ ] **Step 6: Коммит**

```bash
git add -A
git commit -m "feat: приложение отказывается стартовать не на PostgreSQL"
```

---

## Task 3: Inertia SSR работает

В стартере SSR не настроен: `config/inertia.php` содержит закомментированный путь до бандла, в `package.json` есть скрипт `build:ssr`, но входного файла `resources/js/ssr.tsx` нет и в `vite.config.ts` он не подключён.

**Files:**
- Create: `resources/js/ssr.tsx`
- Modify: `vite.config.ts`, `config/inertia.php`, `package.json`
- Test: `tests/Feature/SsrTest.php`

**Interfaces:**
- Produces: собранный бандл `bootstrap/ssr/ssr.mjs`, процесс SSR на порту 13714

- [ ] **Step 1: Написать падающий тест**

`tests/Feature/SsrTest.php`:

```php
<?php

it('has an ssr entry point', function () {
    expect(base_path('resources/js/ssr.tsx'))->toBeFile();
});

it('points inertia at the ssr bundle', function () {
    expect(config('inertia.ssr.bundle'))->toBe(base_path('bootstrap/ssr/ssr.mjs'));
});

it('enables ssr', function () {
    expect(config('inertia.ssr.enabled'))->toBeTrue();
});
```

- [ ] **Step 2: Запустить и убедиться, что падает**

```bash
php artisan test --filter=SsrTest
```

Ожидаем: FAIL — файла нет, `bundle` равен `null`.

- [ ] **Step 3: Создать точку входа SSR**

`resources/js/ssr.tsx`:

```tsx
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel Starter';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} — ${appName}` : appName),
        resolve: (name) => {
            const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });

            return pages[`./pages/${name}.tsx`];
        },
        setup: ({ App, props }) => <App {...props} />,
    }),
);
```

- [ ] **Step 4: Подключить SSR к сборке**

В `vite.config.ts` в блок `laravel({...})` добавить строку `ssr: 'resources/js/ssr.tsx',` рядом с `input`.

В `config/inertia.php` раскомментировать и включить:

```php
'ssr' => [
    'enabled' => true,
    'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
    'bundle' => base_path('bootstrap/ssr/ssr.mjs'),
],
```

- [ ] **Step 5: Собрать и запустить тесты**

```bash
npm install
npm run build:ssr
php artisan test --filter=SsrTest
```

Ожидаем: 3 passed, файл `bootstrap/ssr/ssr.mjs` существует.

- [ ] **Step 6: Проверить, что разметка приходит с сервера**

```bash
php artisan inertia:start-ssr &
sleep 3
php artisan serve --port=8000 &
sleep 2
curl -s http://127.0.0.1:8000/ | grep -c 'data-page'
```

Ожидаем: не ноль. Затем проверить, что внутри контейнера есть разметка, а не пустой `div`:

```bash
curl -s http://127.0.0.1:8000/ | grep -o '<div id="app"[^>]*>.\{0,80\}'
```

Ожидаем: после открывающего тега идут HTML-элементы, а не сразу `</div>`. Это и есть доказательство, что SSR работает, а не просто настроен.

- [ ] **Step 7: Коммит**

```bash
git add -A
git commit -m "feat: Inertia SSR собирается и отдаёт разметку с сервера"
```

---

## Task 4: Демонстрационное содержимое и тёмная тема удалены

Стартер приезжает с приветственной страницей, переключателем темы и пакетами, которые нам не нужны.

**Files:**
- Delete: `resources/js/pages/welcome.tsx`, `resources/js/pages/settings/appearance.tsx`, `resources/js/components/appearance-*.tsx`, `resources/js/hooks/use-appearance.ts`, `resources/js/components/ui/placeholder-pattern.tsx`, `resources/js/pages/auth/two-factor-challenge.tsx`
- Modify: `resources/views/app.blade.php`, `routes/settings.php`, `routes/web.php`, `config/fortify.php`, `composer.json`, `package.json`
- Test: `tests/Feature/NoDemoContentTest.php`

**Interfaces:**
- Produces: маршрут `/` возвращает страницу `home`, маршрута `settings/appearance` не существует

- [ ] **Step 1: Написать падающий тест**

`tests/Feature/NoDemoContentTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

it('has no appearance route', function () {
    expect(Route::has('appearance.edit'))->toBeFalse();
});

it('has no dark mode logic in the layout', function () {
    $blade = file_get_contents(resource_path('views/app.blade.php'));

    expect($blade)->not->toContain('dark')
        ->and($blade)->not->toContain('prefers-color-scheme');
});

it('has no leftover demo files', function () {
    $forbidden = [
        'resources/js/pages/welcome.tsx',
        'resources/js/pages/settings/appearance.tsx',
        'resources/js/hooks/use-appearance.ts',
        'resources/js/components/ui/placeholder-pattern.tsx',
        'resources/js/pages/auth/two-factor-challenge.tsx',
    ];

    foreach ($forbidden as $path) {
        expect(base_path($path))->not->toBeFile();
    }
});

it('has two factor and passkeys disabled', function () {
    $features = config('fortify.features');
    $names = array_map(fn ($f) => is_array($f) ? ($f['name'] ?? '') : $f, $features);

    expect(implode(',', $names))
        ->not->toContain('two-factor-authentication')
        ->not->toContain('passkeys');
});
```

- [ ] **Step 2: Запустить и убедиться, что падает**

```bash
php artisan test --filter=NoDemoContentTest
```

Ожидаем: FAIL по всем четырём.

- [ ] **Step 3: Удалить файлы и правки**

```bash
rm -f resources/js/pages/welcome.tsx \
      resources/js/pages/settings/appearance.tsx \
      resources/js/hooks/use-appearance.ts \
      resources/js/components/ui/placeholder-pattern.tsx \
      resources/js/pages/auth/two-factor-challenge.tsx
rm -f resources/js/components/appearance-dropdown.tsx resources/js/components/appearance-tabs.tsx
composer remove laravel/sail --dev
npm uninstall @laravel/passkeys input-otp
```

`resources/views/app.blade.php` заменить целиком:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <style nonce="{{ csp_nonce() }}">
            html { background-color: oklch(1 0 0); }
        </style>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
```

Функция `csp_nonce()` появится в Task 10. До этого момента временно поставить `<style>` без атрибута и вернуться сюда.

В `routes/web.php` заменить приветственную страницу:

```php
Route::inertia('/', 'home')->name('home');
```

В `routes/settings.php` удалить строку с `settings/appearance` и блок `.well-known/passkey-endpoints`.

В `config/fortify.php` оставить только три возможности:

```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
],
```

Создать заглушку `resources/js/pages/home.tsx` — каркас без текстов-заглушек:

```tsx
import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <>
            <Head title="" />
            <main className="mx-auto flex min-h-svh max-w-3xl flex-col justify-center px-6" />
        </>
    );
}
```

- [ ] **Step 4: Запустить тесты**

```bash
php artisan test --filter=NoDemoContentTest
```

Ожидаем: 4 passed.

- [ ] **Step 5: Убедиться, что сборка не сломалась**

```bash
npm run build:ssr
```

Ожидаем: сборка проходит. Если падает на импорте удалённого файла — найти и убрать импорт.

- [ ] **Step 6: Коммит**

```bash
git add -A
git commit -m "feat: убраны демо-контент, тёмная тема, двухфакторка и passkeys"
```

---

## Task 5: Шрифт Inter локально, с казахскими буквами

`Instrument Sans` из стартера кириллицу не покрывает. Механизм подключения у Laravel правильный — шрифт скачивается на сборке и отдаётся встроенным `<style>` с поддержкой nonce, — меняем только сам шрифт.

**Files:**
- Modify: `vite.config.ts`, `resources/css/app.css`
- Test: `tests/Feature/FontTest.php`, `tests/e2e/font.spec.ts`

**Interfaces:**
- Produces: семейство `Inter` в CSS-переменной `--font-sans`

- [ ] **Step 1: Написать падающий тест**

`tests/Feature/FontTest.php`:

```php
<?php

it('uses Inter and not Instrument Sans', function () {
    $config = file_get_contents(base_path('vite.config.ts'));
    $css = file_get_contents(resource_path('css/app.css'));

    expect($config)->toContain('Inter')->not->toContain('Instrument Sans');
    expect($css)->toContain('Inter')->not->toContain('Instrument Sans');
});

it('requests the cyrillic subsets', function () {
    expect(file_get_contents(base_path('vite.config.ts')))
        ->toContain('cyrillic');
});
```

- [ ] **Step 2: Запустить и убедиться, что падает**

```bash
php artisan test --filter=FontTest
```

Ожидаем: FAIL.

- [ ] **Step 3: Заменить шрифт**

В `vite.config.ts`:

```ts
fonts: [
    bunny('Inter', {
        weights: [400, 500, 600, 700],
        subsets: ['latin', 'latin-ext', 'cyrillic', 'cyrillic-ext'],
    }),
],
```

В `resources/css/app.css` заменить `'Instrument Sans'` на `'Inter'` в объявлении `--font-sans`.

- [ ] **Step 4: Запустить тесты и собрать**

```bash
php artisan test --filter=FontTest
npm run build:ssr
```

Ожидаем: 2 passed, сборка проходит.

- [ ] **Step 5: Проверить казахские буквы в браузере**

`tests/e2e/font.spec.ts`:

```ts
import { expect, test } from '@playwright/test';

const KAZAKH = 'әғқңөұүһі';

test('renders kazakh letters in Inter, not a fallback', async ({ page }) => {
    await page.goto('/');

    await page.evaluate((letters) => {
        const probe = document.createElement('span');
        probe.id = 'glyph-probe';
        probe.textContent = letters;
        probe.style.fontSize = '100px';
        document.body.appendChild(probe);
    }, KAZAKH);

    const probe = page.locator('#glyph-probe');
    const family = await probe.evaluate((el) => getComputedStyle(el).fontFamily);
    expect(family).toContain('Inter');

    // Ширина строки из девяти реальных глифов заметно больше,
    // чем девять прямоугольников-заглушек отсутствующего символа.
    const width = await probe.evaluate((el) => el.getBoundingClientRect().width);
    expect(width).toBeGreaterThan(300);
});
```

```bash
npx playwright test tests/e2e/font.spec.ts
```

Ожидаем: PASS. Если ширина подозрительно ровная — глифов нет, шрифт подменился системным, и нужно брать `@fontsource-variable/inter` вместо провайдера.

- [ ] **Step 6: Коммит**

```bash
git add -A
git commit -m "feat: шрифт Inter локально, с кириллицей и казахскими буквами"
```

---

## Task 6: Три локали и гибридные адреса

Публичная часть с префиксом `/{locale}/`, кабинет без префикса — язык из профиля.

**Files:**
- Create: `app/Http/Middleware/SetLocale.php`, `lang/{ru,kk,en}/common.php`, `resources/js/lib/i18n.ts`
- Modify: `bootstrap/app.php`, `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/LocaleTest.php`, `tests/js/i18n.test.ts`

**Interfaces:**
- Consumes: `AppServiceProvider::assertSupportedDriver` (Task 2)
- Produces: `SetLocale::SUPPORTED = ['ru', 'kk', 'en']`, общее свойство Inertia `locale: string` и `translations: Record<string, string>`, функция `t(key: string, replacements?: Record<string, string>): string`

- [ ] **Step 1: Написать падающие тесты**

`tests/Feature/LocaleTest.php`:

```php
<?php

// Правильные данные
it('serves each supported locale', function (string $locale) {
    $this->get("/{$locale}")->assertOk();
    expect(app()->getLocale())->toBe($locale);
})->with(['ru', 'kk', 'en']);

// Неправильные данные
it('returns 404 for an unsupported locale', function () {
    $this->get('/de')->assertNotFound();
});

it('returns 404 for a malformed locale', function () {
    $this->get('/RU-ru-ru')->assertNotFound();
});

// Пустые данные
it('redirects the bare root to a locale', function () {
    $this->get('/')->assertRedirect('/ru');
});

it('falls back to ru when the browser sends no language', function () {
    $this->withHeaders(['Accept-Language' => ''])
        ->get('/')
        ->assertRedirect('/ru');
});
```

`tests/js/i18n.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import { makeTranslator } from '@/lib/i18n';

describe('t()', () => {
    it('returns the translation for a known key', () => {
        const t = makeTranslator({ 'auth.login': 'Войти' });
        expect(t('auth.login')).toBe('Войти');
    });

    it('substitutes replacements', () => {
        const t = makeTranslator({ 'greeting': 'Привет, :name' });
        expect(t('greeting', { name: 'Сакен' })).toBe('Привет, Сакен');
    });

    it('returns the key itself when the translation is missing', () => {
        const t = makeTranslator({});
        expect(t('nope.missing')).toBe('nope.missing');
    });

    it('handles an empty dictionary without throwing', () => {
        const t = makeTranslator({});
        expect(() => t('')).not.toThrow();
        expect(t('')).toBe('');
    });
});
```

- [ ] **Step 2: Запустить и убедиться, что падают**

```bash
php artisan test --filter=LocaleTest
npx vitest run tests/js/i18n.test.ts
```

Ожидаем: FAIL в обоих.

- [ ] **Step 3: Реализовать**

`app/Http/Middleware/SetLocale.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['ru', 'kk', 'en'];

    public const DEFAULT = 'ru';

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $fromPath = $request->route('locale');

        if (is_string($fromPath) && in_array($fromPath, self::SUPPORTED, true)) {
            return $fromPath;
        }

        $user = $request->user();

        if ($user && in_array($user->locale, self::SUPPORTED, true)) {
            return $user->locale;
        }

        return self::DEFAULT;
    }

    public static function fromBrowser(Request $request): string
    {
        foreach ($request->getLanguages() as $language) {
            $short = substr($language, 0, 2);

            if (in_array($short, self::SUPPORTED, true)) {
                return $short;
            }
        }

        return self::DEFAULT;
    }
}
```

`routes/web.php`:

```php
<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => redirect('/'.SetLocale::fromBrowser($request)));

Route::prefix('{locale}')
    ->whereIn('locale', SetLocale::SUPPORTED)
    ->group(function () {
        Route::inertia('/', 'home')->name('home');
        Route::inertia('privacy', 'legal/privacy')->name('legal.privacy');
        Route::inertia('terms', 'legal/terms')->name('legal.terms');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
```

`lang/ru/common.php` (и по аналогии `kk`, `en`):

```php
<?php

return [
    'auth' => [
        'login' => 'Войти',
        'register' => 'Зарегистрироваться',
        'logout' => 'Выйти',
    ],
    'nav' => [
        'dashboard' => 'Панель',
        'settings' => 'Настройки',
    ],
];
```

`resources/js/lib/i18n.ts`:

```ts
export type Translations = Record<string, string>;

export function makeTranslator(translations: Translations) {
    return function t(key: string, replacements: Record<string, string> = {}): string {
        let line = translations[key] ?? key;

        for (const [placeholder, value] of Object.entries(replacements)) {
            line = line.replaceAll(`:${placeholder}`, value);
        }

        return line;
    };
}
```

В `HandleInertiaRequests::share()` добавить:

```php
'locale' => app()->getLocale(),
'translations' => Arr::dot(trans('common')),
```

Зарегистрировать `SetLocale` в `bootstrap/app.php` в веб-группе.

- [ ] **Step 4: Запустить тесты**

```bash
php artisan test --filter=LocaleTest
npx vitest run tests/js/i18n.test.ts
```

Ожидаем: 5 passed и 4 passed.

- [ ] **Step 5: Коммит**

```bash
git add -A
git commit -m "feat: три локали с гибридными адресами и хелпер t()"
```

---

## Task 7: Время в UTC, показ в зоне пользователя

**Files:**
- Create: `database/migrations/*_add_locale_and_timezone_to_users.php`, `resources/js/lib/datetime.ts`
- Modify: `app/Models/User.php`, `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/TimezoneTest.php`, `tests/js/datetime.test.ts`

**Interfaces:**
- Consumes: общие свойства Inertia (Task 6)
- Produces: колонки `users.timezone` и `users.locale`, общее свойство `auth.user.timezone`, функция `formatDateTime(iso: string, timezone: string, locale: string): string`

- [ ] **Step 1: Написать падающие тесты**

`tests/Feature/TimezoneTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Правильные данные
it('defaults a new user to Asia/Almaty', function () {
    expect(User::factory()->create()->timezone)->toBe('Asia/Almaty');
});

it('stores timestamps as timestamptz', function () {
    $type = DB::selectOne("
        select data_type from information_schema.columns
        where table_name = 'users' and column_name = 'created_at'
    ")->data_type;

    expect($type)->toBe('timestamp with time zone');
});

it('runs the application in UTC', function () {
    expect(config('app.timezone'))->toBe('UTC');
});

// Неправильные данные
it('rejects an unknown timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => 'Mars/Olympus', 'locale' => 'ru'])
        ->assertSessionHasErrors('timezone');
});

it('rejects an unsupported locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => 'Asia/Almaty', 'locale' => 'de'])
        ->assertSessionHasErrors('locale');
});

// Пустые данные
it('rejects an empty timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('locale.update'), ['timezone' => '', 'locale' => 'ru'])
        ->assertSessionHasErrors('timezone');
});
```

`tests/js/datetime.test.ts`:

```ts
import { describe, expect, it } from 'vitest';
import { formatDateTime } from '@/lib/datetime';

describe('formatDateTime', () => {
    // Правильные данные: UTC полдень в Алматы это 17:00 (UTC+5)
    it('shifts UTC into the user timezone', () => {
        const result = formatDateTime('2026-07-27T12:00:00Z', 'Asia/Almaty', 'ru');
        expect(result).toContain('17:00');
    });

    it('renders the same instant differently per timezone', () => {
        const almaty = formatDateTime('2026-07-27T12:00:00Z', 'Asia/Almaty', 'ru');
        const utc = formatDateTime('2026-07-27T12:00:00Z', 'UTC', 'ru');
        expect(almaty).not.toBe(utc);
    });

    // Неправильные данные
    it('returns an em dash for an unparseable value', () => {
        expect(formatDateTime('не дата', 'Asia/Almaty', 'ru')).toBe('—');
    });

    it('falls back to UTC for an unknown timezone', () => {
        expect(() => formatDateTime('2026-07-27T12:00:00Z', 'Mars/Olympus', 'ru')).not.toThrow();
    });

    // Пустые данные
    it('returns an em dash for null', () => {
        expect(formatDateTime(null, 'Asia/Almaty', 'ru')).toBe('—');
    });
});
```

- [ ] **Step 2: Запустить и убедиться, что падают**

```bash
php artisan test --filter=TimezoneTest
npx vitest run tests/js/datetime.test.ts
```

Ожидаем: FAIL.

- [ ] **Step 3: Реализовать**

Миграция:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('timezone', 64)->default('Asia/Almaty');
        $table->string('locale', 5)->default('ru');
    });
}
```

`resources/js/lib/datetime.ts`:

```ts
export function formatDateTime(
    iso: string | null | undefined,
    timezone: string,
    locale: string,
): string {
    if (!iso) {
        return '—';
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat(locale, {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: timezone,
        }).format(date);
    } catch {
        return new Intl.DateTimeFormat(locale, {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: 'UTC',
        }).format(date);
    }
}
```

Маршрут в `routes/settings.php`, внутри группы `auth`:

```php
Route::get('settings/locale', [LocaleController::class, 'edit'])->name('locale.edit');
Route::patch('settings/locale', [LocaleController::class, 'update'])->name('locale.update');
```

`app/Http/Controllers/Settings/LocaleController.php`:

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class LocaleController extends Controller
{
    public function edit(Request $request): Response
    {
        return inertia('settings/locale', [
            'timezones' => DateTimeZone::listIdentifiers(),
            'locales' => SetLocale::SUPPORTED,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
        ]);

        $request->user()->update($validated);

        return back();
    }
}
```

- [ ] **Step 4: Запустить тесты**

```bash
php artisan migrate --force
php artisan test --filter=TimezoneTest
npx vitest run tests/js/datetime.test.ts
```

Ожидаем: 6 passed и 5 passed.

- [ ] **Step 5: Коммит**

```bash
git add -A
git commit -m "feat: время в UTC, таймзона и локаль в профиле, форматирование через Intl"
```

---

## Task 8: UUIDv7 и citext в базовых миграциях

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php` и остальные базовые миграции
- Create: `database/migrations/*_enable_postgres_extensions.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/PostgresSchemaTest.php`

**Interfaces:**
- Consumes: защита драйвера (Task 2)
- Produces: `users.id` типа `uuid` со значением по умолчанию `uuidv7()`, `users.email` типа `citext`

- [ ] **Step 1: Написать падающие тесты**

`tests/Feature/PostgresSchemaTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Правильные данные
it('gives users a uuid primary key defaulted by the database', function () {
    $column = DB::selectOne("
        select data_type, column_default from information_schema.columns
        where table_name = 'users' and column_name = 'id'
    ");

    expect($column->data_type)->toBe('uuid')
        ->and($column->column_default)->toContain('uuidv7()');
});

it('assigns an id even on a raw insert bypassing Eloquent', function () {
    DB::insert("insert into users (name, email, password) values (?, ?, ?)", [
        'Тест', 'raw@example.test', 'x',
    ]);

    $id = DB::selectOne("select id from users where email = 'raw@example.test'")->id;

    expect($id)->not->toBeNull();
});

it('stores email as citext', function () {
    $type = DB::selectOne("
        select udt_name from information_schema.columns
        where table_name = 'users' and column_name = 'email'
    ")->udt_name;

    expect($type)->toBe('citext');
});

// Неправильные данные
it('treats emails differing only in case as duplicates', function () {
    User::factory()->create(['email' => 'Saken@Example.KZ']);

    expect(fn () => User::factory()->create(['email' => 'saken@example.kz']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

// Пустые данные
it('has no users right after a fresh migration', function () {
    expect(User::count())->toBe(0);
})->skip(fn () => User::count() > 0, 'база не пуста');
```

- [ ] **Step 2: Запустить и убедиться, что падают**

```bash
php artisan test --filter=PostgresSchemaTest
```

Ожидаем: FAIL.

- [ ] **Step 3: Реализовать**

Новая миграция первой по порядку, `database/migrations/0000_01_01_000000_enable_postgres_extensions.php`:

```php
public function up(): void
{
    DB::statement('create extension if not exists citext');
    DB::statement('create extension if not exists pg_trgm');
    DB::statement('create extension if not exists btree_gin');
}
```

В миграции пользователей заменить ключ и почту:

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('email')->unique();
    // ...
    $table->timestampsTz();
});

DB::statement('alter table users alter column id set default uuidv7()');
DB::statement('alter table users alter column email type citext');
```

В `App\Models\User` добавить `use HasUuids;` и `public function uniqueIds(): array { return []; }` — идентификатор выдаёт база, модель его не генерирует.

- [ ] **Step 4: Пересобрать базу и запустить тесты**

```bash
php artisan migrate:fresh --force
php artisan test --filter=PostgresSchemaTest
```

Ожидаем: 5 passed.

- [ ] **Step 5: Коммит**

```bash
git add -A
git commit -m "feat: UUIDv7 по умолчанию в базе, citext для почты, timestamptz везде"
```

---

## Task 9: Страницы ошибок вместо модального окна

**Files:**
- Create: `resources/js/pages/errors/error-page.tsx`, `resources/views/errors/{404,419,500,503}.blade.php`
- Modify: `bootstrap/app.php`, `resources/js/app.tsx`
- Test: `tests/Feature/ErrorPagesTest.php`, `tests/e2e/errors.spec.ts`

**Interfaces:**
- Consumes: локали (Task 6)
- Produces: страница `errors/error-page` со свойствами `{ status: number, title: string, description: string }`

- [ ] **Step 1: Написать падающие тесты**

`tests/Feature/ErrorPagesTest.php`:

```php
<?php

use Inertia\Testing\AssertableInertia;

// Правильные данные
it('renders a full inertia page for 404', function () {
    $this->get('/ru/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('errors/error-page')
            ->where('status', 404));
});

it('renders a full inertia page for 403', function () {
    Route::get('/test-403', fn () => abort(403));

    $this->get('/test-403')
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('status', 403));
});

// Неправильные данные
it('redirects 419 to login instead of showing an error page', function () {
    Route::post('/test-419', fn () => abort(419));

    $this->post('/test-419')
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');
});

// Пустые данные
it('has a blade fallback for every handled status', function () {
    foreach ([404, 419, 500, 503] as $status) {
        expect(resource_path("views/errors/{$status}.blade.php"))->toBeFile();
    }
});

it('renders the blade fallback without any external request', function () {
    $blade = file_get_contents(resource_path('views/errors/500.blade.php'));

    expect($blade)->not->toContain('http://')->not->toContain('https://');
});
```

`tests/e2e/errors.spec.ts`:

```ts
import { expect, test } from '@playwright/test';

test('404 is a page, not a modal overlay', async ({ page }) => {
    const response = await page.goto('/ru/no-such-page');
    expect(response?.status()).toBe(404);

    // Модалка Inertia рисует iframe поверх содержимого — его быть не должно
    await expect(page.locator('iframe')).toHaveCount(0);
    await expect(page.getByText('404')).toBeVisible();
});
```

- [ ] **Step 2: Запустить и убедиться, что падают**

```bash
php artisan test --filter=ErrorPagesTest
```

Ожидаем: FAIL.

- [ ] **Step 3: Реализовать обработчик**

В `bootstrap/app.php`, блок `withExceptions`:

```php
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

$exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
    if (! $request->header('X-Inertia')) {
        return $response;
    }

    if ($response->getStatusCode() === 419) {
        return redirect()->guest(route('login'))
            ->with('status', trans('common.errors.session_expired'));
    }

    if (! in_array($response->getStatusCode(), [403, 404, 429, 500, 503], true)) {
        return $response;
    }

    $status = $response->getStatusCode();

    return inertia('errors/error-page', [
        'status' => $status,
        'title' => trans("common.errors.{$status}.title"),
        'description' => trans("common.errors.{$status}.description"),
    ])->toResponse($request)->setStatusCode($status);
});
```

**Про модальное окно.** Отдельно отключать его не нужно, и это важно понимать. Inertia показывает ответ в модальном окне только тогда, когда сервер вернул **не-Inertia-ответ** — обычный HTML вместо ожидаемого JSON. Обработчик выше устраняет саму причину: для каждого обрабатываемого статуса сервер теперь возвращает нормальную страницу Inertia, и показывать в окне становится нечего.

Поэтому проверка идёт не по коду, а по поведению — тест Playwright ниже утверждает, что на странице ноль элементов `iframe`. Если окно всё-таки появилось, значит какой-то статус проскочил мимо списка в обработчике, и чинить надо список, а не клиент.

`resources/js/pages/errors/error-page.tsx`:

```tsx
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props {
    status: number;
    title: string;
    description: string;
}

export default function ErrorPage({ status, title, description }: Props) {
    return (
        <>
            <Head title={title} />
            <main className="mx-auto flex min-h-svh max-w-md flex-col items-center justify-center gap-4 px-6 text-center">
                <p className="text-sm font-medium text-muted-foreground">{status}</p>
                <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                <p className="text-muted-foreground">{description}</p>
                <Button asChild>
                    <Link href="/">На главную</Link>
                </Button>
            </main>
        </>
    );
}
```

`resources/views/errors/500.blade.php` — со встроенными стилями, без единой внешней ссылки:

```blade
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Что-то пошло не так</title>
    <style>
        body { margin: 0; min-height: 100svh; display: grid; place-items: center;
               font-family: system-ui, sans-serif; color: #18181b; background: #fff; }
        main { text-align: center; padding: 1.5rem; max-width: 28rem; }
        p.code { font-size: .875rem; color: #71717a; margin: 0 0 .5rem; }
        h1 { font-size: 1.5rem; margin: 0 0 .5rem; }
        p.text { color: #71717a; margin: 0 0 1.5rem; }
        a { display: inline-block; padding: .5rem 1rem; border-radius: .625rem;
            background: #18181b; color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <p class="code">500</p>
        <h1>Что-то пошло не так</h1>
        <p class="text">Мы уже знаем о проблеме и разбираемся.</p>
        <a href="/">На главную</a>
    </main>
</body>
</html>
```

Аналогично для 404, 419, 503 — со своими текстами. Системный шрифт здесь намеренно: этот файл должен работать, когда не работает вообще ничего, включая сборку.

Добавить тексты в `lang/{ru,kk,en}/common.php` под ключом `errors`.

- [ ] **Step 4: Запустить тесты**

```bash
php artisan test --filter=ErrorPagesTest
npx playwright test tests/e2e/errors.spec.ts
```

Ожидаем: 5 passed и 1 passed.

- [ ] **Step 5: Проверить самый важный случай — упавший SSR**

```bash
pkill -f inertia:start-ssr
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/ru/no-such-page
```

Ожидаем: `404`, а не `500` и не пустой ответ. Затем поднять SSR обратно.

- [ ] **Step 6: Коммит**

```bash
git add -A
git commit -m "feat: ошибки показываются страницей, 419 ведёт на вход, есть blade-запасной вариант"
```

---

## Task 10: CSP и заголовки безопасности

**Files:**
- Create: `app/Http/Middleware/SecurityHeaders.php`, `app/Support/Csp/AppPolicy.php`
- Modify: `bootstrap/app.php`, `resources/views/app.blade.php`
- Test: `tests/Feature/SecurityHeadersTest.php`

**Interfaces:**
- Consumes: шаблон `app.blade.php` (Task 4)
- Produces: заголовок `Content-Security-Policy` с nonce, функция `csp_nonce()`

- [ ] **Step 1: Установить пакет**

```bash
composer require spatie/laravel-csp
php artisan vendor:publish --tag=csp-config
```

- [ ] **Step 2: Написать падающие тесты**

`tests/Feature/SecurityHeadersTest.php`:

```php
<?php

// Правильные данные
it('sends a content security policy', function () {
    $this->get('/ru')->assertHeader('Content-Security-Policy');
});

it('restricts default-src to self', function () {
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'");
});

it('sends the hardening headers', function () {
    $response = $this->get('/ru');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

// Неправильные данные
it('does not allow any external host in the policy', function () {
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    expect($csp)->not->toContain('http://')->not->toContain('https://');
});

it('does not use unsafe-inline for scripts', function () {
    $csp = $this->get('/ru')->headers->get('Content-Security-Policy');

    expect($csp)->not->toContain("script-src 'unsafe-inline'");
});

// Пустые данные
it('still sends the policy on an error page', function () {
    $this->get('/ru/no-such-page')
        ->assertNotFound()
        ->assertHeader('Content-Security-Policy');
});
```

- [ ] **Step 3: Запустить и убедиться, что падают**

```bash
php artisan test --filter=SecurityHeadersTest
```

Ожидаем: FAIL.

- [ ] **Step 4: Реализовать**

`app/Http/Middleware/SecurityHeaders.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ]);

        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }
}
```

`app/Support/Csp/AppPolicy.php` — политика без единого внешнего хоста:

```php
<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;

class AppPolicy implements Policy
{
    public function configure(): void
    {
        csp()
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            ->add(Directive::IMG, [Keyword::SELF, 'data:'])
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->addNonce(Directive::SCRIPT);
    }
}
```

`unsafe-inline` для стилей оставлен намеренно: Laravel отдаёт шрифт встроенным блоком `<style>`, а Tailwind в режиме разработки внедряет стили инлайн. Для скриптов это недопустимо и заменено на nonce.

Прописать `AppPolicy` в `config/csp.php` и зарегистрировать оба middleware в `bootstrap/app.php`. Вернуться в `resources/views/app.blade.php` и поставить `nonce="{{ csp_nonce() }}"` в блок `<style>`, как указано в Task 4.

- [ ] **Step 5: Запустить тесты**

```bash
php artisan test --filter=SecurityHeadersTest
```

Ожидаем: 6 passed.

- [ ] **Step 6: Проверить, что ничего не сломалось в браузере**

```bash
npm run build:ssr
npx playwright test
```

Ожидаем: все тесты проходят, в консоли браузера нет сообщений о нарушении CSP. Если есть — политика правится, а не отключается.

- [ ] **Step 7: Прогнать весь набор**

```bash
php artisan test
npx vitest run
npx playwright test
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

Ожидаем: всё зелёное. Это условие завершения этапа.

- [ ] **Step 8: Коммит**

```bash
git add -A
git commit -m "feat: CSP с nonce и заголовки безопасности, внешние хосты запрещены"
```

---

## Task 11: Проект объясняет себя ИИ-инструментам

Стартер клонируют и продолжают в связке с ИИ-агентом. Без правил в корне каждый новый агент начинает с угадывания: поставит SQLite «для тестов», приделает тёмную тему, притащит Google Fonts, сложит список и форму на один маршрут. Файлы правил — это не документация, а ограничитель.

Задача идёт последней в этапе намеренно: описываем то, что реально есть, а не то, что планировалось.

**Files:**
- Create: `AGENTS.md`, `CLAUDE.md`, `zettel/README.md`, `.cursor/rules/project.mdc`
- Test: `tests/Feature/AgentRulesTest.php`

**Interfaces:**
- Produces: `AGENTS.md` как единственный источник правды; `CLAUDE.md` и правило Cursor ссылаются на него, а не дублируют

- [ ] **Step 1: Написать падающий тест**

`tests/Feature/AgentRulesTest.php`:

```php
<?php

// Правильные данные
it('has agent instructions in the project root', function () {
    expect(base_path('AGENTS.md'))->toBeFile()
        ->and(base_path('CLAUDE.md'))->toBeFile()
        ->and(base_path('zettel/README.md'))->toBeFile();
});

it('states every hard rule', function () {
    $rules = file_get_contents(base_path('AGENTS.md'));

    foreach (['PostgreSQL', 'zettel', 'UTC', 'Asia/Almaty', 'ru', 'kk', 'en', 'CSP'] as $needle) {
        expect($rules)->toContain($needle);
    }
});

// Неправильные данные: правила не должны противоречить коду
it('does not promise anything the code contradicts', function () {
    $rules = file_get_contents(base_path('AGENTS.md'));

    expect($rules)->not->toContain('SQLite')
        ->not->toContain('Redis')
        ->not->toContain('dark mode');
});

// Пустые данные: CLAUDE.md не дублирует, а ссылается
it('keeps a single source of truth', function () {
    $claude = file_get_contents(base_path('CLAUDE.md'));

    expect(trim($claude))->toContain('@AGENTS.md')
        ->and(strlen($claude))->toBeLessThan(500);
});
```

- [ ] **Step 2: Запустить и убедиться, что падает**

```bash
php artisan test --filter=AgentRulesTest
```

Ожидаем: FAIL — файлов нет.

- [ ] **Step 3: Написать `AGENTS.md`**

```markdown
# Правила проекта

Стартер на Laravel + Inertia React SSR. Этот файл — единственный источник
правды о правилах. `CLAUDE.md` и `.cursor/rules/project.mdc` ссылаются сюда.

## Заменить при клонировании

- Название приложения в `.env` (`APP_NAME`)
- Имя базы данных (`DB_DATABASE`)
- Этот абзац

## Стек

Laravel 13, Fortify, Inertia 3 в режиме SSR, React 19, TypeScript,
Tailwind 4, shadcn/ui, PostgreSQL 18. PHP 8.5, Node 25.

## Жёсткие правила

1. **Только PostgreSQL.** Драйвер `pgsql`, в тестах тоже. Приложение падает
   на старте при любом другом драйвере — это сделано намеренно, не чините
   это отключением проверки.
2. **Первичные ключи — `uuid` со значением `uuidv7()` на уровне базы.**
   Не генерируйте идентификаторы в моделях.
3. **Все временные колонки — `timestamptz`.** Приложение работает в UTC,
   зона пользователя лежит в `users.timezone` (по умолчанию `Asia/Almaty`).
   Сервер никогда не отдаёт готовую строку с датой — только ISO 8601,
   форматирует клиент через `Intl.DateTimeFormat`.
4. **Ни одного внешнего домена при отрисовке.** Ни шрифтов, ни иконок,
   ни аналитики, ни капчи. Это проверяется заголовками CSP — если добавили
   что-то внешнее, страница просто сломается.
5. **Только светлая тема.** Тёмной нет и не планируется.
6. **Три локали: `ru`, `kk`, `en`.** Новый текст добавляется во все три.
   Публичная часть с префиксом `/{locale}/`, кабинет без префикса.
7. **Список и форма — разные экраны.** `/entity` — список,
   `/entity/new` — создание, `/entity/:id` — редактирование.
   Короткая форма может быть модальным окном, большая — отдельной страницей.
   Не сваливайте всё на один маршрут.
8. **Никакого демонстрационного содержимого.** Всё, что попадает в проект,
   должно быть рабочим функционалом.

## Тесты

Каждая фича — **три уровня × три состояния данных**:

- уровни: Pest (бэкенд), Vitest с Testing Library (отрисовка), Playwright (клики);
- состояния: правильные данные, неправильные, пустые.

Итого минимум девять сценариев на фичу. Написанный, но не запущенный тест
не считается написанным — прогоняйте и показывайте вывод.

## Картотека

У проекта есть `zettel/` — она ведётся с первого дня и **обязательна**.
Одна карточка — одна мысль. Появилось решение, договорённость или новый
кусок кода — сразу новая карточка, а не «потом соберу».
Правила оформления: `zettel/README.md`.

## Команды

    php artisan serve            приложение
    npm run dev                  сборка в режиме разработки
    php artisan inertia:start-ssr процесс SSR (нужен всегда)
    php artisan queue:work       очередь
    php artisan test             тесты бэкенда
    npx vitest run               тесты отрисовки
    npx playwright test          тесты взаимодействия
    vendor/bin/pint              форматирование
    vendor/bin/phpstan analyse   статический анализ

Postgres.app держит бинарники вне `PATH`:
`/Applications/Postgres.app/Contents/Versions/latest/bin`

## Где что лежит

    app/Http/Middleware/SetLocale.php        определение локали
    app/Http/Middleware/SecurityHeaders.php  заголовки безопасности
    app/Support/Csp/AppPolicy.php            политика CSP
    resources/js/ssr.tsx                     точка входа SSR
    resources/js/lib/datetime.ts             форматирование времени
    resources/js/lib/i18n.ts                 хелпер t()
    resources/views/errors/                  запасные страницы ошибок
    lang/{ru,kk,en}/                         переводы
    docs/superpowers/specs/                  спецификации
    docs/superpowers/plans/                  планы реализации

## Страницы ошибок

Ошибки показываются обычными страницами, а не модальным окном. Причина
окна — не-Inertia-ответ от сервера, поэтому обработчик в `bootstrap/app.php`
возвращает страницу Inertia для каждого статуса. Blade-версии в
`resources/views/errors/` нужны на случай, когда упал сам процесс SSR, —
не удаляйте их и не добавляйте туда внешних ссылок.

Статус 419 обрабатывается перенаправлением на вход, а не страницей ошибки.

## Чего в проекте намеренно нет

Redis, Docker, тёмная тема, двухфакторная аутентификация, passkeys,
вход через соцсети, Sanctum и API, векторный поиск, внешние трекеры ошибок.
Прежде чем добавить что-то из этого списка — спросите.
```

- [ ] **Step 4: Написать `CLAUDE.md` — ссылкой, а не копией**

```markdown
# CLAUDE.md

Правила проекта живут в одном месте, чтобы не разъезжались:

@AGENTS.md
```

`.cursor/rules/project.mdc`:

```markdown
---
description: Правила проекта
alwaysApply: true
---

Правила проекта описаны в `AGENTS.md` в корне репозитория. Прочитайте его
перед любой правкой кода и следуйте ему.
```

- [ ] **Step 5: Написать `zettel/README.md`**

```markdown
# Картотека проекта

Одна карточка — одна мысль. Три коротких карточки лучше одной длинной.

## Что записываем

- **Функционал** — что делает и почему сделано именно так
- **Требования и решения** — своими словами, не копипастой сообщения
- **Обсуждения** — к чему пришли, что отвергли и почему
- **Новый код** — что появилось, зачем, как связано с остальным

## Формат

Отдельный `.md`-файл, короткое имя-слаг латиницей. Заголовок — сама мысль
одной фразой, а не тема. Дальше несколько предложений по делу.

Плохой заголовок: «Про базу данных».
Хороший: «PostgreSQL 18 даёт uuidv7 встроенной функцией — расширение не нужно».

## Связи

Ссылки между карточками через `[[имя-карточки]]`. Ссылаться щедро.
Ссылка на ещё не написанную карточку — это не ошибка, а пометка, что её
стоит написать.

## Когда писать

Сразу. Появилась по ходу задачи новая мысль — сразу новая карточка.
```

- [ ] **Step 6: Запустить тесты**

```bash
php artisan test --filter=AgentRulesTest
```

Ожидаем: 4 passed.

- [ ] **Step 7: Проверить, что правила не разъехались с кодом**

Пройти по списку жёстких правил и убедиться, что каждое подтверждается тестом из предыдущих задач:

| Правило | Подтверждается |
|---|---|
| Только PostgreSQL | `DatabaseDriverGuardTest` |
| UUIDv7 из базы | `PostgresSchemaTest` |
| `timestamptz` и UTC | `TimezoneTest` |
| Нет внешних доменов | `SecurityHeadersTest` |
| Нет тёмной темы | `NoDemoContentTest` |
| Три локали | `LocaleTest` |
| Ошибки страницей | `ErrorPagesTest` |

Если правило нельзя привязать к тесту — либо оно лишнее, либо не хватает теста.

- [ ] **Step 8: Коммит**

```bash
git add -A
git commit -m "feat: правила проекта для ИИ-инструментов в AGENTS.md, картотека объясняет себя"
```

---

## Проверка этапа

Этап 1 считается закрытым, когда одновременно верно:

1. `php artisan test` — зелёный, тестовая база на PostgreSQL.
2. `npx vitest run` — зелёный.
3. `npx playwright test` — зелёный, поверх работающего SSR.
4. `vendor/bin/pint --test` и `vendor/bin/phpstan analyse` — без замечаний.
5. `curl http://127.0.0.1:8000/ru` возвращает разметку, отрисованную сервером, а не пустой контейнер.
6. При остановленном процессе SSR адрес несуществующей страницы возвращает 404 с оформленной страницей, а не пустой ответ.
7. В консоли браузера нет нарушений CSP и нет запросов к чужим доменам во вкладке «Сеть».
8. `AGENTS.md` описывает то, что действительно есть, и каждое жёсткое правило в нём подтверждается тестом.

---

## Не входит в этап и требует отдельного решения

**Визуальный язык в стиле Apple.** Спецификация, раздел 7, оставляет его открытым: «конкретный визуальный язык согласуется отдельно на макетах до начала вёрстки». Задачи под него в этом плане нет намеренно — палитру, шкалу отступов, скругления, тени и типографику словами не согласовать.

Пока это не решено, задачи 4 и 9 используют оформление, приехавшее со стартером. После согласования макетов появится отдельный план: токены оформления, переработка компонентов shadcn, лэйауты гостя и приложения.

---

## Что дальше

Этап 2 — авторизация: экраны входа, регистрации, сброса пароля и подтверждения почты под shadcn, настройки профиля, сессии и устройства, удаление аккаунта. План пишется отдельно после закрытия первого этапа.

При каждом этапе `AGENTS.md` обновляется вместе с кодом — устаревшие правила хуже отсутствующих.
