# Project rules

Laravel + Inertia React SSR + PostgreSQL starter. This file is the single
source of truth. `CLAUDE.md` and the Cursor rule point here so the copies
cannot drift apart.

## Language — think in English, answer in Russian

**Everything an agent reads or writes is English:** code, comments,
`AGENTS.md`, `zettel/`, `docs/`, commit messages, test names, variable
names. Do not write Russian into any of them.

**Answer the project owner in Russian.** Explanations, summaries, status
updates, questions — Russian only.

**Product text is not English by default.** UI strings live in
`lang/{ru,kk,en}` and follow the localisation rules below.

The reason is cost: Cyrillic costs roughly twice as many tokens per
character as Latin. Measured on this repo, English cuts the agent-facing
files by ~41%. Nobody reads them but agents, so there is nothing to trade
away.

## Replace when cloning

- `APP_NAME` and `DB_DATABASE` in `.env`
- The `Asia/Almaty` default in the users migration if the project is not Kazakhstani
- The placeholder mark: `resources/js/components/app-logo-icon.tsx`,
  `public/favicon.svg`, `public/favicon.ico`, `public/apple-touch-icon.png`
- The legal pages: `legal.privacy.body.*` and `legal.terms.body.*` in
  `lang/{ru,kk,en}/common.php`. Headings ship, wording does not — every
  unfilled section says so in red on the page.
- `BACKUP_NOTIFICATION_EMAIL`, `FAILED_JOB_EMAILS`, `HEALTH_JSON_SECRET`
- This section

## Stack

Laravel 13, Fortify, Inertia 3 (SSR), React 19, TypeScript, Tailwind 4,
shadcn/ui, PostgreSQL 18. PHP 8.5, Node 25.

## Hard rules

1. **PostgreSQL only.** Driver `pgsql`, in tests too. The app refuses to
   boot on anything else — that is deliberate, do not "fix" it by
   disabling the check. The schema relies on `uuidv7()`, `citext` and
   `timestamptz`, none of which exist elsewhere.

2. **The database connection runs in UTC.** Keep `'timezone' => 'UTC'`
   in the `pgsql` config. PHP sends timestamps without an offset and
   Postgres reads them in its session timezone — the instant shifts
   silently otherwise.

3. **Identifiers are UUIDv7, generated in two places on purpose.**
   `HasUuids` sets the key on the model; the database default covers
   inserts that bypass Eloquent. Both are needed.

4. **Every timestamp column is `timestamptz`.** The app runs in UTC, the
   user's zone lives in `users.timezone`. **The server never sends a
   preformatted date** — only ISO 8601; the client formats it through
   `Intl.DateTimeFormat` in `resources/js/lib/datetime.ts`.

5. **No external domain at render time.** No fonts, icons, analytics or
   captcha. The browser enforces this through CSP, not goodwill — which
   is why reCAPTCHA is impossible here by construction.
   **Any CSP change ends with opening a page in a real browser and
   reading the console.** Green tests prove nothing here.

6. **Design tokens live in `resources/css/app.css`.** The language is
   dense macOS System Settings: 13px in the panel, 17px on public pages,
   7px radius, hairlines instead of shadows, accent `#0071e3` on one
   line. **Token changes end at `/dev/styleguide` in a browser, not at a
   green test run** — three styling bugs in a row returned HTTP 200 with
   valid markup and nothing for a test to catch.

7. **Mobile first, and that is the base class.** A Tailwind class with
   no prefix is the phone; `sm:` and up are the concession to a big
   screen. Written the other way round the page does not degrade, it
   breaks — a row of fixed columns inside `overflow-hidden` simply cuts
   its content off at 375px, silently and without a scrollbar.

   **A touch target is 44px.** Dense controls are fine where there is a
   cursor: `size-11 sm:size-7`. The one that matters most is the button
   that opens the navigation — on a phone it is the only way in.

   A list is a row on a wide screen and a card on a narrow one. See
   `pages/dash/users/index.tsx`: the header hides below `sm`, because a
   header labels columns and a card has none.

   Anything changed inside `components/ui/` is upstream shadcn and will
   be overwritten by an update. Mark such a change with a comment saying
   why, so it can be reapplied deliberately rather than found missing.

8. **Light theme only.** There is no dark theme and none is planned. The
   `dark:` variants inside `components/ui/` are upstream shadcn and never
   fire without a `dark` class on `<html>`. Do not add `dark:` to your
   own code.

9. **Three locales: `ru`, `kk`, `en`.** New copy goes into all three at
   once — a test compares the key sets. Public pages sit under
   `/{locale}/`, the cabinet does not. On the front end "go home" is `/`,
   not `home()`.

   Static objects (page `layout`, breadcrumbs, nav items) hold a
   translation **key**, not text: a static object cannot call a hook, so
   the component resolves it.

10. **Errors are pages, not modals.** Inertia draws a modal when the
   server answers with plain HTML; the handler in `bootstrap/app.php`
   removes the cause. If a modal appears, a status slipped past the list
   — fix the list. The blade versions in `resources/views/errors/` work
   when nothing else does: no `@vite`, no external links, system font.

11. **A list is a list, a form is a form.** `/entity` lists,
    `/entity/new` creates, `/entity/:id` edits. A short form may be a
    dialog, a long one gets its own page. Never both on one route.

    The panel root is `/dash` (route `dash.home`) and every panel
    resource sits under it. Nothing may live at `/dashboard`.

12. **Access is gated by permissions, never by a flag.** Permissions are
    declared in `App\Support\Access\Ability` because a permission means
    something only when code checks it; inventing one through the UI
    produces a name nothing enforces. Which permissions a role carries is
    a business decision and lives in the panel.

    **The screen shows what the person may do.** `auth.abilities` comes
    down in the shared props and `useCan()` reads it, so the navigation
    stops offering a section that would answer 403. It decides nothing —
    anyone can edit what the browser holds — and every route is still
    gated by its policy. It only stops the interface from lying about
    what it can do.

    `RoleSeeder` is idempotent and runs on every deploy, so a permission
    added in code appears without anyone creating it by hand. Tests seed
    it too — roles are infrastructure, not fixtures, and without them
    every policy denies everything for the wrong reason.

13. **No demo content.** Everything that ships is working functionality.

## Conventions over configuration

A file's location follows from the entity name. This is not taste — the
`make:feature` generator relies on it.

    Post
    ├─ app/Models/Post.php
    ├─ app/Policies/PostPolicy.php
    ├─ app/Http/Controllers/Dash/PostController.php
    ├─ app/Http/Requests/Post/StoreRequest.php
    ├─ app/Http/Requests/Post/UpdateRequest.php
    ├─ resources/js/pages/dash/posts/index.tsx
    ├─ resources/js/pages/dash/posts/form.tsx
    ├─ lang/{ru,kk,en}/entities/posts.php
    └─ tests/{Feature/Dash,js}/Post*

Tables are plural snake_case, models singular StudlyCase, routes RESTful,
and the Inertia page path mirrors the URL.

**`make:feature` emits all of it.** Do not hand-write a typical entity:

    php artisan make:feature Post --fields=title:string,body:text
    php artisan migrate
    php artisan wayfinder:generate --with-form
    npm run build          # the blade template includes the new page eagerly,
                           # so backend tests 500 until the build exists
    php artisan test && npm run test:js

Field types: `string`, `text`, `integer`, `boolean`, `date`, `datetime`,
and `belongsTo`.

    php artisan make:feature Post --fields=title:string,category:belongsTo

`category:belongsTo` writes the foreign key, the relation on the model,
the eager load on the list, a select on the form and an `exists` rule.
The key is nullable and `nullOnDelete`: deleting a category orphans its
posts rather than deleting them, which is a business decision a project
overrides deliberately.

The select labels each option with the related model's **first fillable
attribute** — for an entity this generator made, the first field its
author named. Reference an entity whose first field is not a human label
and the list shows the wrong column.

**`hasMany` and `belongsToMany` are hand-written.** `hasMany` is the
inverse of `belongsTo` and owns no schema. `belongsToMany` needs a pivot
table and a multi-select, and guessing either would be worse than the
half hour of writing them. Counted across three real projects: 19
`belongsTo`, 22 `belongsToMany`, 11 `hasMany` — so the generator covers
the side that owns a column, and the rest is deliberate work. The generated slice
comes with 13 backend and 8 rendering tests that pass with no edits.
`--force` overwrites; without it existing files are left alone.

**These conventions cover `/dash`.** The generator emits an admin CRUD
slice: a list, a search box, a dialog. That is the right shape for a
panel and the wrong one for a public page, and pretending otherwise
would produce a news site whose articles open in a modal.

For public content the convention is smaller and written by hand:

    /{locale}/articles           list, paginated, named `articles.index`
    /{locale}/articles/{article} detail, bound by slug, named `articles.show`

    Route::prefix('{locale}')->whereIn('locale', Locales::SUPPORTED)->group(...)

Four things a public page owes that a panel page does not:

- **A slug, not a uuid, in the URL.** `getRouteKeyName()` on the model
  returns `slug`; the uuid stays the key.
- **A place in the sitemap.** The static pages are listed in
  `SitemapController`; a model-backed section implements
  `App\Support\Seo\HasSitemapEntries` and is registered in
  `config/seo.php`. Skip it and the sitemap describes three pages of a
  site that has a thousand.
- **A title and description per locale**, passed to `<Head>`. A public
  page that shares the application's title is a page search engines
  cannot tell apart from every other.
- **No `auth` middleware, and therefore no assumption of a user.** Shared
  props carry `auth.user = null`; a page that reads it without checking
  breaks for exactly the visitors it was built for.

**What is deliberately absent** — this is one big monolith, not a layer
cake: no repositories (Eloquent is one), no service layer (logic lives in
models, form requests and policies), no action class per operation, no
interfaces with a single implementation, no CQRS.

The ban is on the habit, not on the idea. Work that genuinely is not a
model's — talking to another system, generating a document, driving a
subprocess — goes in **`App\Support`**, next to `Locale`, `Sessions`,
`Access`, `Seo` and `Sse`. What is forbidden is the reflex of wrapping
four Eloquent calls in a `UserService` because a layer diagram says
there should be one.

`tests/Feature/ArchitectureTest.php` enforces this. Controllers may not
touch the database directly — put the query on the model or in
`app/Support`.

**Conventions bind the typical, not the unusual.** Trees, versioning and
translatable content are written by hand, and the deviation gets a card
in `zettel/`.

## Tests

Every feature: **three levels × three data states**.

- Levels: Pest (backend), Vitest + Testing Library (rendering), Playwright (clicks).
  `npm run test:e2e` builds the assets, resets its own `_e2e` database and
  starts SSR, so it is production-shaped and slow to start. It is the only
  level that catches a page returning 200 with everything broken.
- States: correct data, wrong data, empty data.

Nine scenarios minimum. A test that was written but never run does not
count — run it and show the output.

Tests run against a real PostgreSQL. Do not switch them to SQLite for
speed: `uuidv7()`, `citext` and `timestamptz` then go unverified and the
difference surfaces in production instead.

The element marker is `data-test`, not `data-testid` — Testing Library is
configured for it and Playwright uses the same attribute.

## Zettelkasten

`zettel/` is mandatory and kept from day one. One card, one thought.
A decision or a new piece of code means a new card right away, not "later".
Format: `zettel/README.md`.

## Commands

    composer dev                 server, queue, logs, vite
    php artisan test             backend tests
    npm run test:js              rendering tests
    npx tsc --noEmit             type check
    vendor/bin/pint              PHP formatting
    npm run test:e2e             real clicks in a real browser
    composer types:check         static analysis
    npm run build:ssr            build including the SSR bundle
    php artisan wayfinder:generate --with-form   regenerate TS routes
    /dev/styleguide              every component at once (APP_ENV=local only)

`--with-form` is not optional: without it the `.form()` variant disappears
and the whole auth flow breaks.

Postgres.app keeps its binaries out of `PATH`:
`/Applications/Postgres.app/Contents/Versions/latest/bin`

Mail in development is Mailpit on `127.0.0.1:1025`, web UI on `:8025`.

## Where things live

    app/Http/Middleware/SetLocale.php        locale resolution
    app/Http/Middleware/SecurityHeaders.php  security headers
    app/Support/Access/Ability.php           permission names, declared in code
    app/Support/Csp/AppPreset.php            CSP policy
    app/Support/Sessions/SessionList.php     active sessions and devices
    app/Support/Spam/SilentRedirectResponder.php  what happens on honeypot spam
    app/Providers/HealthServiceProvider.php  which health checks run
    app/Http/Controllers/SitemapController.php  sitemap.xml and robots.txt
    routes/console.php                       the schedule
    app/Exceptions/UnsupportedDatabaseDriver.php  database driver guard
    bootstrap/app.php                        error handler, middleware
    resources/js/lib/datetime.ts             time formatting
    resources/js/lib/i18n.ts                 the t() helper
    resources/js/hooks/use-translations.ts   the hook around it
    resources/js/pages/errors/               error page
    resources/views/errors/                  fallback error pages
    lang/{ru,kk,en}/                         UI copy
    lang/{ru,kk,en}/entities/                per-entity copy
    scripts/check-font-glyphs.py             deep font check
    docs/superpowers/specs/                  specifications
    docs/superpowers/plans/                  implementation plans

## SSR

No hand-written `ssr.tsx`: in Inertia 3 the `@inertiajs/vite` plugin finds
the entry point and injects the server bootstrap itself.

**Development needs no separate SSR process** — rendering goes through the
Vite dev server, which is why `composer dev` does not start one.

**Production does:** `php artisan inertia:start-ssr` over a
`npm run build:ssr` bundle. Keeping it alive is systemd's job.

**The SSR process holds the bundle in memory.** Rebuilding changes the file
on disk; the running process never notices and keeps serving the old UI.
So `npm run build:ssr` always comes paired with a restart — in checks and
on deploy alike.

The bundle is `bootstrap/ssr/app.js`, not the `ssr.mjs` from Inertia 2
docs. Getting that path wrong fails **silently**: production serves an
empty container with nothing in the logs.

**The port is 13714 for every Inertia project and cannot be changed** —
the plugin bakes it into the bundle. Two of them on one development
machine share a renderer: the first to start answers for both, and the
second gets `Page not found: <component>` in a log it does not own while
its own pages come back empty. A browser hides this, because the client
bundle hydrates anyway. `SsrTest` asks the running server for a page only
this project has, which is what catches it.

## Speed

**An index is added after a measurement, never before one.** `explain
analyze` against a realistic number of rows, and `pg_stat_user_indexes`
afterwards to see whether the planner actually took it. Two composite
indexes were written for the activity page on perfectly sound reasoning
and then measured: `idx_scan = 0` for both. They would have slowed every
write to buy nothing.

**A few rows prove nothing.** On a small table Postgres prefers a
sequential scan whatever indexes exist, so a plan taken against an empty
development database says only that the database is empty.

**Application caching is allowed and is the last resort, not the first.**
Fix the query, then the index, then cache. Where it is used, say in the
comment what was measured and how long the entry lives — see
`Activity::distinctLogNames()`, where two `distinct` scans cost 51 ms on
every page load and now cost nothing.

**Tests flush the cache between cases.** It outlives the transaction
`RefreshDatabase` rolls back, so a value cached by one test would be
served to the next as though the rows behind it still existed.

## Operations

Five things run outside the request cycle, and every one fails silently:
nothing errors, the work simply stops happening.

    php artisan user:admin <email>  the first administrator — see below
    php artisan health:check        queue, schedule, backups, disk, database
    /dash/health                    the same results, gated by health.view
    /health.json                    for a monitor; needs HEALTH_JSON_SECRET

**Health checks are not run by the page.** `health:check` writes results on
the schedule and the page reads them. A page that ran them would fire a disk
and database check on every refresh, and hang on a check that hangs.

**Spatie's own health dashboard is unusable here** — it opens with a
stylesheet from `rsms.me`, which the CSP blocks. Hence our own page.

**Three checks only exist in production** — debug mode, environment,
optimized app. They assert how production is configured and are meant to be
false in development; registered everywhere they would paint the dashboard
permanently red, and a dashboard that is always red is one nobody reads.

**Backups go to `storage/app/private/<APP_NAME>/` nightly.** `pg_dump` must
be on the PATH of whoever runs cron. Success notifications are off on
purpose: a mail every night is a mail nobody opens, and then the failure
mail goes unread too.

**Run `schedule-monitor:sync` after touching `routes/console.php`,** or the
monitor keeps watching for a task that no longer exists.

**`php artisan user:admin <email>` on every fresh install.** Roles are
seeded and permissions are declared in code, but nothing hands a role to
a person: without this every policy denies, `/dash` answers 403, and the
only way in is tinker. The command creates the account or promotes it,
and is safe to run twice.

**`REGISTRATION_ENABLED=false` closes registration** — the routes stop
existing. Most projects built on this want that: accounts are made by an
administrator. It defaults to open only because the starter always has.

**`TRUSTED_PROXIES` is empty by default and should stay that way** under
the nginx→PHP-FPM deployment in README: nginx already passes the real
client address and marks the request secure. Put a CDN or a balancer in
front and it must be set, or every visitor shares one address — the login
limiter becomes one bucket for the internet, the sessions page shows the
same address for everybody, and HSTS silently stops being sent.

## Sign-in

**Five attempts a minute, per email and address** — the limiter is in
`FortifyServiceProvider::configureRateLimiting`. It is the reason the
end-to-end suite signs in once in a setup project and reuses the cookies:
a suite that logs in per test exhausts it inside the first minute, and
every test after that waits for a redirect that never comes.

**The cabinet has no locale in its URL,** so the login page is rendered in
whatever language the browser asks for. Tests select by `data-test`, never
by copy.

**An already signed-in person opening `/login` goes to `/dash`.** Laravel's
default sends them to `/`, which here redirects to the public home page —
following an old bookmark then looks like being signed out.

## Live feed (SSE)

The activity log streams over Server-Sent Events. Three things about it
are easy to get wrong and impossible to catch with a green test run:

- **An open connection holds a worker, and the arithmetic is knowable.**
  A viewer costs `lifetime / (lifetime + retry)` workers — 0.9 at the
  defaults, which is one worker each in all but name. Thirty workers is
  about twenty-seven viewers before the rest of the site queues behind
  them. Both numbers are in `config/sse.php`; the stream sends `retry`
  itself rather than leaving it to the browser, because that is the only
  lever the server has.

  **In production give the stream its own FPM pool** — README has the
  recipe. Then a saturated feed costs the feed and nothing else.

  The built-in dev server is single threaded, so one viewer blocks the
  whole app: start it as `PHP_CLI_SERVER_WORKERS=6 php artisan serve`.
- **`NOTIFY` is delivered at COMMIT.** Inside an uncommitted transaction
  no listener ever hears it, which is why two tests write through a
  connection of their own.
- **`pg_connect()` reuses a matching connection.** `PostgresListener`
  passes `PGSQL_CONNECT_FORCE_NEW`; anything else opening libpq by hand
  must too, or somebody's `pg_close()` ends a wait that is in progress.

## Deliberately absent

Redis, Docker, dark theme, two-factor auth, passkeys, social login,
Sanctum and any API, vector search, external error trackers, AI agents
(moved to the `feature/ai-agents` branch).

**Livewire and Laravel Pulse.** Pulse was installed, gated and opened in
a browser: it loads a font from fonts.bunny.net, its dashboard never
leaves the loading skeleton under the CSP, and it drags in Livewire — a
second frontend runtime beside React. Removed. See
`zettel/pulse-does-not-fit-a-strict-csp.md` before trying again.

Ask before adding anything from this list.
