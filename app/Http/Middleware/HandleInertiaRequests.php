<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;

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
                'user' => $request->user(),
            ],
            'locale' => app()->getLocale(),
            'locales' => SetLocale::SUPPORTED,
            // Flat dictionary: key 'nav.dashboard' instead of nested
            // objects, which keeps t() at twenty lines with no tree walk.
            // Entity dictionaries are prefixed, so 'users.title' cannot
            // collide with 'nav.title' from the common one.
            'translations' => array_merge(
                Arr::dot(trans('common')),
                collect(glob(lang_path(app()->getLocale().'/entities/*.php')))
                    ->mapWithKeys(fn (string $file) => Arr::dot(
                        [basename($file, '.php') => require $file],
                    ))
                    ->all(),
            ),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
