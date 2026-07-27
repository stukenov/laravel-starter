<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Sessions\SessionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Show the user's active sessions.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/sessions', [
            'sessions' => SessionList::forUser($request->user(), $request->session()->getId()),
        ]);
    }

    /**
     * Log the user out of every other device.
     */
    public function destroy(Request $request): RedirectResponse
    {
        SessionList::revokeOthers($request->user(), $request->session()->getId());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('common.sessions.logged_out_others'),
        ]);

        return back();
    }
}
