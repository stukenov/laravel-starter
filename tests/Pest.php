<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Honeypot\EncryptedTime;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

/*
 * Roles and permissions are infrastructure, not fixtures: without them the
 * policies deny everything and every panel test fails for the wrong reason.
 * The registrar is reset first because it caches the permission list for
 * the whole process, and the process outlives a single test database.
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(RoleSeeder::class);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * The honeypot fields a real browser submits.
 *
 * Registration requires them: the middleware is strict there on purpose,
 * because a bot that simply omits them would otherwise be waved through.
 * The timestamp has to be in the past — the point of it is that a form
 * cannot be filled in faster than a person types.
 */
function honeypot(): array
{
    return [
        config('honeypot.name_field_name') => '',
        config('honeypot.valid_from_field_name') => EncryptedTime::create(now()->subMinute()),
    ];
}
