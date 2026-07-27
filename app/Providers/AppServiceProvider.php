<?php

namespace App\Providers;

use App\Exceptions\UnsupportedDatabaseDriver;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Honeypot\ProtectAgainstSpam;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        self::assertSupportedDriver(DB::connection()->getDriverName());

        $this->configureDefaults();
        $this->protectRegistrationFromBots();
        $this->trustConfiguredProxies();

        // Where an already signed-in person goes if they open /login or
        // /register. Laravel's default is `/`, which here redirects to the
        // public home page — so following an old bookmark signs you out of
        // the panel in appearance while leaving you signed in.
        RedirectIfAuthenticated::redirectUsing(fn () => config('fortify.home'));
    }

    /**
     * RULE(security): who may say where a request came from — AGENTS.md.
     *
     * Configured here rather than in `bootstrap/app.php`, because `env()`
     * there returns null the moment `php artisan optimize` caches the
     * config — which is to say, in production, which is the only place
     * this setting matters. Nothing would have errored; the proxies would
     * simply have stopped being trusted.
     */
    private function trustConfiguredProxies(): void
    {
        $proxies = config('security.trusted_proxies');

        if ($proxies === []) {
            return;
        }

        TrustProxies::at($proxies === ['*'] ? '*' : $proxies);
        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );
    }

    /**
     * RULE(external): no third-party domain at render time — AGENTS.md.
     *
     * Which rules out every captcha by construction: the browser would
     * block the script. A honeypot needs nothing but our own HTML — a
     * field a person never sees and a bot fills in anyway.
     *
     * Registration is the only public form, so the middleware goes on that
     * one route rather than the whole web group. Fortify declares the
     * route itself and offers no hook, so it is amended afterwards; route
     * middleware is read per request, long after boot.
     *
     * Two traps, both silent. It has to wait for `booted`, because this
     * provider boots before the route files are loaded. And it scans the
     * collection instead of calling getByName(), because the name index is
     * rebuilt in a `booted` callback of its own that runs after this one —
     * so the lookup finds nothing and leaves registration unguarded with
     * no sign that anything went wrong.
     */
    private function protectRegistrationFromBots(): void
    {
        $this->app->booted(function (): void {
            foreach (Route::getRoutes()->getRoutes() as $route) {
                if ($route->getName() === 'register.store') {
                    $route->middleware(ProtectAgainstSpam::class);
                }
            }
        });
    }

    /**
     * RULE(db): the check is deliberate, do not disable it — AGENTS.md.
     *
     * The schema relies on uuidv7(), citext and timestamptz. None exist
     * on other drivers, so running on SQLite is not "slightly worse" —
     * it is quietly wrong: tests go green while production breaks.
     *
     * @throws UnsupportedDatabaseDriver
     */
    public static function assertSupportedDriver(string $driver): void
    {
        if ($driver !== 'pgsql') {
            throw UnsupportedDatabaseDriver::forDriver($driver);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
