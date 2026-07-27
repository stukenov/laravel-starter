<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocaleUpdateRequest;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LocaleController extends Controller
{
    /**
     * Show the user's language and timezone settings.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/locale', [
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    /**
     * Update the user's language and timezone.
     */
    public function update(LocaleUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings updated.')]);

        return back();
    }
}
