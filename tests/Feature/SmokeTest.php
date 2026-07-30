<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('runs on postgresql', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

it('has native uuidv7 available', function () {
    $value = DB::selectOne('select uuidv7() as id')->id;

    expect($value)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-/');
});

it('has the extensions the schema will need', function () {
    $available = collect(DB::select("
        select name from pg_available_extensions
        where name in ('citext', 'pg_trgm', 'btree_gin')
    "))->pluck('name')->all();

    expect($available)->toContain('citext', 'pg_trgm', 'btree_gin');
});

it('runs the application in UTC', function () {
    expect(config('app.timezone'))->toBe('UTC');
});

it('serves the home page under a locale', function () {
    // Root now redirects to a locale; the page lives under the prefix.
    $this->get('/')->assertRedirect();
    $this->get('/ru')->assertOk();
});
