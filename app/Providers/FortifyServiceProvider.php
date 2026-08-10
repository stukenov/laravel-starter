<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Spatie\Honeypot\Honeypot;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->throttlePasswordReset();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
            // The password-reset request is a public form that writes, and
            // it had no bot protection at all: the honeypot was attached to
            // registration alone. Close registration — the state most
            // projects built on this want — and the only public write form
            // left was unguarded. See AGENTS.md.
            'honeypot' => app(Honeypot::class)->toArray(),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            // The fields a captcha would have been. Rendered by the page
            // rather than a blade component, and only here: registration
            // is the only form a stranger can reach. See AGENTS.md.
            'honeypot' => app(Honeypot::class)->toArray(),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($this->throttleKey($request));
        });

        /*
         * RULE(security): every public form that sends mail is limited.
         *
         * Fortify declares the password-reset routes with `guest` and
         * nothing else. Laravel's own `passwords.users.throttle` only
         * stops a second token being issued for the same address within a
         * minute — it does not stop the requests, so the form still
         * accepts as many as anyone cares to send.
         *
         * That is enough to fill somebody's inbox with password-reset mail
         * indefinitely, and enough to keep the mail queue busy with made-up
         * addresses. The sign-in form has had a limiter from the start;
         * this is the other half of the same door.
         */
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($this->throttleKey($request));
        });
    }

    /** Per address and per email, so one noisy sender cannot lock out a street. */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(
            $request->string(Fortify::username())->lower()->value().'|'.$request->ip(),
        );
    }

    /**
     * Fortify hard-codes the password-reset routes without a limiter and
     * offers no hook, so it is attached afterwards — the same way the
     * honeypot is attached to registration in AppServiceProvider.
     *
     * `booted`, and by scanning rather than getByName(): the name index is
     * rebuilt in a booted callback of its own, so a lookup here finds
     * nothing and leaves the routes unguarded with no sign of it.
     */
    private function throttlePasswordReset(): void
    {
        $this->app->booted(function (): void {
            foreach (Route::getRoutes()->getRoutes() as $route) {
                if (in_array($route->getName(), ['password.email', 'password.update'], true)) {
                    $route->middleware('throttle:password-reset');
                }
            }
        });
    }
}
