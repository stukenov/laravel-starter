# Laravel starter

Laravel 13 · Inertia 3 (React 19, SSR) · Tailwind 4 · shadcn/ui · PostgreSQL 18.

The rules this project runs on are in [AGENTS.md](AGENTS.md). Read that
first — it is the single source of truth, and `CLAUDE.md` only points at it.

## What is already here

- Sign in, register, reset password, verify email, sessions across devices
- Roles and permissions, an audit log, a live feed of it over SSE
- Health checks, backups, schedule monitoring, failed-job alerts
- Three locales (`ru`, `kk`, `en`), UTC storage with per-user timezones
- Error pages that are pages, a strict CSP, a local font, no external calls
- `make:feature`, which writes a whole CRUD slice with its tests

## Running it

Requires PHP 8.5, Node 25, PostgreSQL 18.

    composer install
    npm install
    cp .env.example .env && php artisan key:generate
    createdb laravel_starter
    php artisan migrate --seed
    composer dev

`composer dev` starts the server, the queue, the log tail and Vite. SSR needs
no separate process in development.

If another Inertia project is running its SSR server, stop it first. The port
is 13714 for all of them and cannot be changed per project — they would share
one renderer and quietly serve each other empty pages.

Postgres.app keeps its binaries out of `PATH`:

    export PATH="/Applications/Postgres.app/Contents/Versions/latest/bin:$PATH"

Mail goes to Mailpit on `127.0.0.1:1025`, readable at `:8025`.

## Tests

Three levels, and a feature is not finished until all three pass.

    php artisan test      # backend
    npm run test:js       # rendering
    npm run test:e2e      # real clicks in a real browser

The end-to-end run builds the assets, resets its own `_e2e` database and
starts the SSR process, so it takes a minute before the first test runs.

## Renaming it

Everything to replace when cloning is listed at the top of
[AGENTS.md](AGENTS.md#replace-when-cloning): the application name, the
default timezone, the placeholder mark and favicons, and the legal pages.

The legal pages ship with their section headings and no wording. Each empty
section says so, in red, on the page — fill `legal.privacy.body.*` and
`legal.terms.body.*` in `lang/{ru,kk,en}/common.php` before going live.

## Deploying

Nothing here needs Redis, Docker or a message broker. A server with PHP-FPM,
nginx, PostgreSQL and cron is enough.

### First time

    git clone <repo> /var/www/app && cd /var/www/app
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build:ssr
    cp .env.example .env && php artisan key:generate

Set in `.env`:

    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://example.com
    DB_CONNECTION=pgsql
    HEALTH_JSON_SECRET=<a long random string>
    BACKUP_NOTIFICATION_EMAIL=ops@example.com
    FAILED_JOB_EMAILS=ops@example.com

Then:

    php artisan migrate --force --seed
    php artisan schedule-monitor:sync
    php artisan optimize

### Three processes, not one

The web server alone is not enough. Each of these fails silently if you
forget it — nothing errors, work simply stops happening.

**Queue.** Systemd unit, restarted on exit:

    ExecStart=/usr/bin/php /var/www/app/artisan queue:work --tries=3 --max-time=3600
    Restart=always

**SSR.** Same shape, and it must restart on every deploy:

    ExecStart=/usr/bin/php /var/www/app/artisan inertia:start-ssr
    Restart=always

The SSR process holds the bundle in memory. Rebuilding changes the file on
disk and the running process never notices — it keeps serving the old UI
until it is restarted. This is the single most common way a deploy looks
successful and is not.

**Cron.** One line drives every scheduled task:

    * * * * * cd /var/www/app && php artisan schedule:run >> /dev/null 2>&1

### Every deploy after that

    php artisan down
    git pull
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build:ssr
    php artisan migrate --force
    php artisan schedule-monitor:sync
    php artisan optimize
    systemctl restart app-ssr app-queue
    php artisan up

### nginx

Server-sent events need buffering off, or the live feed arrives in one lump
when the connection closes:

    location /dash/activity/stream {
        proxy_buffering off;
        gzip off;
        fastcgi_buffering off;
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    }

Each open stream holds an FPM worker for 25 seconds, so `pm.max_children`
sets the ceiling on how many people can watch the feed at once.

### Checking it works

    php artisan health:check          # queue, schedule, backups, disk, database
    curl -H "X-Health-Secret: …" https://example.com/health.json

The panel shows the same results at `/dash/health`. The JSON route does not
exist unless `HEALTH_JSON_SECRET` is set — an open one lists which parts of
the system are currently failing.

Backups land in `storage/app/private/<APP_NAME>/` nightly. `pg_dump` has to
be on the PATH of the user cron runs as.
