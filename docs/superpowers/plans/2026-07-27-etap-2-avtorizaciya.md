# Этап 2 — авторизация и первый срез. План реализации

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Довести авторизацию до готового вида на трёх языках под принятым оформлением, добавить управление сессиями и устройствами, собрать первый полный вертикальный срез и извлечь из него генератор `make:feature`.

**Architecture:** Логика авторизации уже есть — её даёт Fortify. Работа этапа не в написании логики, а в экранах: локализация, плотность, оформление. Единственная действительно новая функция — сессии и устройства. Завершается этап срезом «пользователи» и генератором, порождённым из него.

**Tech Stack:** Laravel 13, Fortify, Inertia 3 SSR, React 19, Tailwind 4, shadcn/ui, PostgreSQL 18, Pest 4, Vitest, matomo/device-detector.

## Global Constraints

Действуют в каждой задаче, повторно не упоминаются. Полный список — `AGENTS.md`.

- **Только PostgreSQL**, зона соединения UTC, ключи UUIDv7, даты `timestamptz`.
- **Ни одного внешнего домена** при отрисовке; правка CSP проверяется в браузере.
- **Только светлая тема.** Плотность: `density-panel` в кабинете, `density-public` на публичных страницах.
- **Три локали** — новый текст сразу в `ru`, `kk`, `en`; тест сверяет совпадение ключей.
- **Список и форма — разные экраны.**
- **Тесты: 3 уровня × 3 состояния** на фичу. Незапущенный тест не считается написанным.
- **После `npm run build:ssr` перезапускать процесс SSR** — иначе проверяешь старый код.
- **Правка оформления заканчивается открытием `/dev/styleguide` в браузере**, а не прогоном тестов.
- Каждая задача заканчивается коммитом и карточкой в `zettel/`, если появилась мысль.

---

## Порядок и обоснование

Генератор просили в этом этапе, «как только появится первый срез». Авторизация полного CRUD не даёт: там формы без списков. Поэтому **пользователи из этапа 3 переносятся сюда** — они и есть первый срез, из которого извлекается генератор. Порядок:

1. Экраны авторизации — локализация и оформление
2. Сессии и устройства — единственная новая функция
3. Настройки профиля — локализация и оформление
4. Пользователи в `/dash` — первый полный срез
5. Генератор `make:feature` из этого среза

---

## Task 1: Экраны авторизации на трёх языках

Страницы приезжают от стартера с английским текстом в разметке. Логика Fortify не трогается — меняются только экраны.

**Files:**
- Modify: `resources/js/pages/auth/*.tsx`, `resources/js/layouts/auth/*.tsx`
- Modify: `lang/{ru,kk,en}/common.php`
- Create: `resources/js/hooks/use-translations.ts`
- Test: `tests/Feature/Auth/LocalizedAuthTest.php`, `tests/js/use-translations.test.tsx`

**Interfaces:**
- Consumes: `makeTranslator` из `lib/i18n.ts`, общие свойства `translations` и `locale`
- Produces: хук `useTranslations(): (key: string, replacements?) => string`

- [ ] **Step 1: Написать падающие тесты**

`tests/js/use-translations.test.tsx` проверяет хук на трёх состояниях: есть перевод, нет перевода, пустой словарь.

`tests/Feature/Auth/LocalizedAuthTest.php`:

```php
<?php

use App\Models\User;

it('serves the login page in every locale', function (string $locale) {
    $this->withCookie('locale', $locale)->get(route('login'))->assertOk();
})->with(['ru', 'kk', 'en']);

it('shows validation errors in the active locale', function () {
    $user = User::factory()->create(['locale' => 'ru']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => '', 'email' => 'битая'])
        ->assertSessionHasErrors(['name', 'email']);

    expect(session('errors')->first('email'))->not->toContain('The email');
});

it('has no hardcoded english left on auth screens', function () {
    foreach (glob(resource_path('js/pages/auth/*.tsx')) as $file) {
        $contents = file_get_contents($file);

        foreach (['Log in to your account', 'Enter your email', 'Forgot your password'] as $phrase) {
            expect($contents)->not->toContain($phrase);
        }
    }
});
```

- [ ] **Step 2: Прогнать и убедиться, что падают**

```bash
php artisan test --filter=LocalizedAuthTest && npx vitest run
```

- [ ] **Step 3: Написать хук и локализовать переводы**

`resources/js/hooks/use-translations.ts`:

```ts
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { makeTranslator } from '@/lib/i18n';

export function useTranslations() {
    const { translations } = usePage().props;

    return useMemo(() => makeTranslator(translations), [translations]);
}
```

Добавить в `lang/{ru,kk,en}/common.php` раздел `auth` со всеми строками экранов: заголовки, подписи полей, кнопки, ссылки, сообщения о статусе.

Перевести валидацию: опубликовать `lang/en/validation.php` и добавить `ru`, `kk`.

- [ ] **Step 4: Переписать экраны**

В каждом файле `resources/js/pages/auth/*.tsx` заменить английские строки на `t('auth.…')`. Лэйауты авторизации получают класс `density-public`.

- [ ] **Step 5: Прогнать тесты и проверить в браузере**

```bash
php artisan test --filter=LocalizedAuthTest
npx vitest run
npm run build:ssr && pkill -f 'bootstrap/ssr'; node bootstrap/ssr/app.js &
```

Открыть `/ru`, `/kk`, `/en` и `/login` — убедиться, что текст на нужном языке и консоль чистая.

- [ ] **Step 6: Коммит**

---

## Task 2: Сессии и устройства

Единственная новая функция этапа. Требует драйвера сессий в базе — он уже включён.

**Files:**
- Create: `app/Http/Controllers/Settings/SessionController.php`, `app/Support/Sessions/SessionList.php`
- Create: `resources/js/pages/settings/sessions.tsx`
- Modify: `routes/settings.php`, `resources/js/layouts/settings/layout.tsx`
- Test: `tests/Feature/Settings/SessionsTest.php`, `tests/js/sessions.test.tsx`

**Interfaces:**
- Produces: `SessionList::forUser(User $user, string $currentId): array` — каждый элемент `{id, device, platform, browser, ip, last_active_at, is_current}`

- [ ] **Step 1: Поставить разбор user-agent**

```bash
composer require matomo/device-detector
```

- [ ] **Step 2: Написать падающие тесты**

`tests/Feature/Settings/SessionsTest.php` — девять сценариев:

*правильные:* список показывает текущую сессию с пометкой; чужая сессия видна; удаление остальных оставляет текущую живой.
*неправильные:* нельзя удалить сессию другого пользователя; аноним получает редирект; удаление требует подтверждения пароля.
*пустые:* у пользователя без записей список пуст; неизвестный user-agent даёт «Неизвестное устройство»; пустой ip не ломает страницу.

- [ ] **Step 3: Реализовать**

`SessionList` читает таблицу `sessions`, разбирает `user_agent` через `DeviceDetector`, помечает текущую по идентификатору сессии. Частичный индекс на активных сессиях уже создан миграцией этапа 1.

Контроллер отдаёт список и удаляет все сессии пользователя кроме текущей. Маршрут защищён `RequirePassword`.

- [ ] **Step 4: Экран**

`settings/sessions.tsx` — список устройств, у текущего пометка, кнопка «Выйти со всех остальных» в диалоге подтверждения. Плотность панели.

- [ ] **Step 5: Прогнать всё и проверить в браузере**

- [ ] **Step 6: Коммит**

---

## Task 3: Настройки профиля под новым языком

**Files:**
- Modify: `resources/js/pages/settings/{profile,security,locale}.tsx`, `resources/js/components/delete-user.tsx`
- Modify: `lang/{ru,kk,en}/common.php`
- Test: `tests/Feature/Settings/LocalizedSettingsTest.php`

- [ ] **Step 1: Тесты** — три экрана × три состояния: страница открывается, валидация ругается на своём языке, пустая форма даёт ошибки по всем полям.

- [ ] **Step 2: Локализовать и применить `density-panel`.**

- [ ] **Step 3: Прогнать, проверить в браузере, коммит.**

---

## Task 4: Первый вертикальный срез — пользователи в `/dash`

Образец, из которого извлекается генератор. Каждый файл кладётся строго по конвенции из `AGENTS.md` — иначе генератор будет порождать не то, что мы написали руками.

**Files:**
- Create: `app/Policies/UserPolicy.php`, `app/Http/Controllers/Dash/UserController.php`,
  `app/Http/Requests/User/StoreRequest.php`, `app/Http/Requests/User/UpdateRequest.php`
- Create: `resources/js/pages/dash/users/index.tsx`, `resources/js/pages/dash/users/form.tsx`
- Create: `lang/{ru,kk,en}/entities/users.php`
- Create: `routes/dash.php`
- Test: `tests/Feature/Dash/UserTest.php`, `tests/js/dash-users.test.tsx`

**Interfaces:**
- Consumes: `search_vector` и `pg_trgm`-индекс из миграции этапа 1
- Produces: маршруты `dash.users.{index,store,update,destroy}`

- [ ] **Step 1: Тесты — девять сценариев минимум**

*правильные:* список открывается и показывает пользователей; поиск находит по имени и по почте; создание работает; редактирование меняет поля.
*неправильные:* дубль почты отвергается; чужой без права получает 403; удаление себя запрещено.
*пустые:* пустой список показывает пустое состояние; поиск без совпадений даёт ноль строк; пустая форма даёт ошибки по всем полям.

- [ ] **Step 2: Реализовать по конвенции**

Поиск использует `search_vector` при длинном запросе и `pg_trgm` при коротком — на двух-трёх буквах полнотекстовый поиск не срабатывает, и это надо покрыть тестом.

Список — `/dash/users`, форма — модальное окно (форма короткая, по правилу из `AGENTS.md`).

- [ ] **Step 3: Прогнать всё, проверить в браузере, коммит.**

---

## Task 5: Генератор `make:feature`

Извлекается из среза пользователей. **Требование заказчика: генератор полный.**

**Files:**
- Create: `app/Console/Commands/MakeFeatureCommand.php`
- Create: `stubs/feature/*` — шаблоны всех пятнадцати файлов
- Test: `tests/Feature/MakeFeatureTest.php`

- [ ] **Step 1: Тест приёмки — самый важный в этапе**

```php
it('generates a slice whose tests pass without a single edit', function () {
    Artisan::call('make:feature', ['name' => 'Note', '--fields' => 'title:string,body:text']);
    Artisan::call('migrate', ['--force' => true]);

    $result = Process::run('php artisan test --filter=NoteTest');

    expect($result->successful())->toBeTrue($result->output());
});
```

- [ ] **Step 2: Шаблоны пятнадцати файлов**

Миграция, модель, фабрика, политика, два запроса формы, контроллер, регистрация маршрута, страница списка, страница формы, типы TypeScript, три файла переводов, три файла тестов.

- [ ] **Step 3: Проверка на пустоту тестов**

```php
it('generates tests that actually assert something', function () {
    Artisan::call('make:feature', ['name' => 'Note', '--fields' => 'title:string']);

    $test = file_get_contents(base_path('tests/Feature/Dash/NoteTest.php'));

    // Заглушка вместо теста хуже отсутствия теста: она создаёт видимость
    // покрытия, и следующий человек ей верит.
    expect($test)->not->toContain('markTestSkipped')
        ->and($test)->not->toContain('->skip(')
        ->and(substr_count($test, 'expect('))->toBeGreaterThanOrEqual(9);
});
```

- [ ] **Step 4: Уборка за тестом** — сгенерированная сущность удаляется, миграция откатывается.

- [ ] **Step 5: Прогнать всё, коммит.**

---

## Проверка этапа

Этап 2 закрыт, когда одновременно верно:

1. `php artisan test`, `npm run test:js`, `composer types:check`, `vendor/bin/pint --test` — зелёные.
2. Экраны авторизации и настроек открываются на всех трёх языках, консоль браузера чистая.
3. Список сессий показывает текущее устройство и позволяет выйти с остальных.
4. `/dash/users` работает: список, поиск, создание, редактирование, удаление.
5. `php artisan make:feature Note --fields=title:string` порождает срез, все тесты которого проходят **без единой правки**.
6. `AGENTS.md` обновлён под появившиеся возможности.

## Что дальше

Этап 3 — остаток панели: роли, права, журнал действий с живым потоком по SSE, лендинг, эксплуатационные пакеты.
