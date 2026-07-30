<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), honeypot() + [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dash.home', absolute: false));
    }
}

/*
 * The flag closes the route. It did not close the invitation.
 *
 * `REGISTRATION_ENABLED=false` unregisters the sign-up route, and the
 * sign-in page went on offering the link regardless — so the one public
 * entrance to a closed application ended in a 404, on exactly the
 * deployment that had been configured most carefully. The page now reads
 * the flag from the shared props.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('tells the sign-in page whether registration is open', function () {
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where(
            'registrationEnabled',
            Features::enabled(Features::registration()),
        ));
});

it('offers the link while registration is open', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('registrationEnabled', true));
});

// ── Wrong data ───────────────────────────────────────────────────────

it('stops offering the link once registration is closed', function () {
    // The route is gone at this point, so an offered link is a 404 with
    // nothing to explain it.
    config(['fortify.features' => array_values(array_filter(
        config('fortify.features'),
        fn ($feature) => $feature !== Features::registration(),
    ))]);

    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('registrationEnabled', false));
});

// ── Empty data ───────────────────────────────────────────────────────

it('sends the flag even to a visitor with no session', function () {
    // Anonymous is the only kind of visitor this page ever has.
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->has('registrationEnabled'));
});
