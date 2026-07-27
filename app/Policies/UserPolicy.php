<?php

namespace App\Policies;

use App\Models\User;

/**
 * RULE(access): managing users is for administrators only — AGENTS.md.
 *
 * This policy used to return true everywhere, with a comment saying real
 * roles arrive in stage 3. That was a genuine hole: registration is open
 * by default, so anyone could sign up and then change any account's email
 * and password — including an administrator's. "Roles come later" is not
 * a reason to ship an open door.
 *
 * `is_admin` is a stopgap. When spatie/laravel-permission lands, this
 * file and the column go together.
 */
class UserPolicy
{
    /**
     * The list exposes every user's email — that is a data leak on its
     * own, not just a management concern.
     */
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, User $target): bool
    {
        return (bool) $user->is_admin || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, User $target): bool
    {
        return (bool) $user->is_admin;
    }

    /**
     * Even an administrator may not delete themselves: one click would
     * strip the system of its last way in. Leaving is a separate,
     * deliberate flow behind a password confirmation.
     */
    public function delete(User $user, User $target): bool
    {
        return (bool) $user->is_admin && $user->id !== $target->id;
    }
}
