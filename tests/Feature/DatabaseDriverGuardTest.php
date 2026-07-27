<?php

use App\Exceptions\UnsupportedDatabaseDriver;
use App\Providers\AppServiceProvider;

// Correct data
// Проверяем на Throwable, а не на конкретный класс: иначе тест зеленеет
// и без реализации — отсутствующий метод бросает Error, который в
// not->toThrow(UnsupportedDatabaseDriver) проходит как «не то исключение».
it('accepts pgsql', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver('pgsql'))
        ->not->toThrow(Throwable::class);
});

it('is actually running on pgsql right now', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver(
        DB::connection()->getDriverName()
    ))->not->toThrow(Throwable::class);
});

// Wrong data
it('rejects sqlite and names it in the message', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver('sqlite'))
        ->toThrow(UnsupportedDatabaseDriver::class, 'sqlite');
});

it('rejects mysql', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver('mysql'))
        ->toThrow(UnsupportedDatabaseDriver::class);
});

it('tells the developer how to fix it', function () {
    try {
        AppServiceProvider::assertSupportedDriver('sqlite');
    } catch (UnsupportedDatabaseDriver $e) {
        expect($e->getMessage())->toContain('DB_CONNECTION=pgsql');

        return;
    }

    $this->fail('exception was not thrown');
});

// Empty data
it('rejects an empty driver', function () {
    expect(fn () => AppServiceProvider::assertSupportedDriver(''))
        ->toThrow(UnsupportedDatabaseDriver::class);
});
