<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Support\Sessions\SessionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/security', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Update the user's password and end every other session.
     *
     * The commonest reason to change a password is the suspicion that
     * somebody else has the account. Changing it alone did nothing about
     * that: a session is authenticated by its own row, not by the
     * password, so the intruder's session went on working and the person
     * who had just "secured" their account had done nothing of the kind.
     *
     * `revokeOthers` rather than `logoutOtherDevices()`: the framework's
     * version rotates a hash in the session and leaves the rows in place,
     * so the sessions page keeps listing devices that are already gone.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        $ended = SessionList::revokeOthers($request->user(), $request->session()->getId());

        // Saying how many is the confirmation that the security action
        // did something. "Password updated." alone leaves the person who
        // came here worried about an intruder none the wiser.
        Inertia::flash('toast', ['type' => 'success', 'message' => $ended > 0
            ? __('common.sessions.password_updated_others_ended', ['count' => $ended])
            : __('Password updated.')]);

        return back();
    }
}
