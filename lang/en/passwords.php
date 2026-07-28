<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Reset Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default lines which match reasons
    | that are given by the password broker for a password update attempt
    | outcome such as failure due to an invalid password / reset token.
    |
    */

    /*
     * `user` says exactly what `sent` says, on purpose.
     *
     * Laravel's default answers "we have emailed you" for an address that
     * exists and "we can't find a user" for one that does not — so anybody
     * could walk a list of addresses through the public form and learn who
     * has an account here. The limiter slows that down; it does not stop
     * it, because the answer itself is the leak.
     */
    'reset' => 'Your password has been reset.',
    'sent' => 'If that address is one of ours, the reset link is on its way.',
    'throttled' => 'Too often. Please wait a moment.',
    'token' => 'That link no longer works — ask for a new one.',
    'user' => 'If that address is one of ours, the reset link is on its way.',

];
