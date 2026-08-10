<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locale\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;
use Laravel\Fortify\Features;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                // RULE(conventions): a model says what may leave it —
                // AGENTS.md. This is the hottest path in the application:
                // every Inertia response for every signed-in person.
                'user' => $request->user()?->toSharedProps(),
                /*
                 * RULE(access): the screen shows what the person may
                 * do — AGENTS.md.
                 *
                 * Without this the panel offered every section to
                 * everybody and let the server say no: a member saw
                 * Users, Roles, Activity and Health in the navigation and
                 * got 403 from each. The permission was enforced, which
                 * is the part that matters, but the interface was lying
                 * about what it could do.
                 *
                 * Names only — the front end never decides anything, it
                 * only stops offering what it knows will be refused.
                 */
                'abilities' => $request->user()?->getAllPermissions()->pluck('name')->all() ?? [],
            ],
            /*
             * `REGISTRATION_ENABLED=false` unregisters the route, and the
             * sign-in page went on offering the link anyway — so the one
             * public entrance to a closed application ended in a 404, on
             * exactly the deployment that had been configured most
             * carefully.
             */
            'registrationEnabled' => Features::enabled(Features::registration()),
            /*
             * The two registration urls, shared rather than imported.
             *
             * `REGISTRATION_ENABLED=false` also stops Wayfinder generating
             * `resources/js/routes/register`, and a page that imports it
             * then fails the *build* — not a test, not a request. Every
             * backend test stayed green and `npm run build` died on
             * MISSING_EXPORT, which is the one place nothing was looking.
             *
             * A runtime guard does not help: the import is static and is
             * resolved whether or not the branch that uses it ever runs.
             * So the pages take the urls from here instead, and there is
             * nothing to resolve when the routes do not exist.
             */
            'registerUrl' => Features::enabled(Features::registration())
                ? route('register')
                : null,
            'registerAction' => Features::enabled(Features::registration())
                ? route('register.store')
                : null,
            'locale' => app()->getLocale(),
            'locales' => Locales::SUPPORTED,
            // Flat dictionary: key 'nav.dashboard' instead of nested
            // objects, which keeps t() at twenty lines with no tree walk.
            // Entity dictionaries are prefixed, so 'users.title' cannot
            // collide with 'nav.title' from the common one.
            'translations' => array_merge(
                Arr::dot(trans('common')),
                collect(glob(lang_path(app()->getLocale().'/entities/*.php')) ?: [])
                    ->mapWithKeys(fn (string $file): array => Arr::dot(
                        [basename($file, '.php') => require $file],
                    ))
                    ->all(),
            ),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
