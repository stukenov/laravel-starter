<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\Sessions\SessionList;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password, then end every
     * session the account has open.
     *
     * A reset is the flow for somebody who has lost control of their
     * account, and it left every existing session running. A session is
     * authenticated by its own row, not by the password, so whoever was
     * already signed in stayed signed in — through the one action the
     * person took specifically to stop them.
     *
     * Every session, not "the others": nobody is signed in here. The
     * reset happens for a visitor with a link from an email.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();

        SessionList::revokeOthers($user, currentSessionId: '');
    }
}
