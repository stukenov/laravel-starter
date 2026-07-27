<?php

namespace App\Providers;

use App\Exceptions\UnsupportedDatabaseDriver;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
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

        // Where an already signed-in person goes if they open /login or
        // /register. Laravel's default is `/`, which here redirects to the
        // public home page — so following an old bookmark signs you out of
        // the panel in appearance while leaving you signed in.
        RedirectIfAuthenticated::redirectUsing(fn () => config('fortify.home'));
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
