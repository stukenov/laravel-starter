<?php

namespace App\Providers;

use App\Exceptions\UnsupportedDatabaseDriver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
