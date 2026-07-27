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
     * ПРАВИЛО(база): проверка намеренная, не отключайте — AGENTS.md.
     *
     * Схема опирается на uuidv7(), citext и timestamptz. Ни того, ни другого,
     * ни третьего на других драйверах нет, поэтому запуск на SQLite не «чуть
     * хуже», а тихо неправильный: тесты зеленеют, а прод ломается.
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
