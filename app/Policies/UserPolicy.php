<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Access\Ability;

/**
 * RULE(access): the panel is gated by permissions, not by a flag.
 *
 * This policy once returned true everywhere with a note saying real roles
 * would arrive later. Registration is open by default, so that was an open
 * door: anyone could sign up and change any account's credentials. The
 * lesson stays even though the flag is gone — a permission check is not
 * something to defer.
 *
 * See AGENTS.md.
 */
class UserPolicy
{
    /**
     * The list exposes every user's email, so viewing is a permission of
     * its own rather than a free side effect of being signed in.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::VIEW_USERS);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(Ability::VIEW_USERS) || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::MANAGE_USERS);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(Ability::MANAGE_USERS);
    }

    /**
     * Nobody deletes themselves here, permission or not: one click would
     * strip the system of its last way in. Leaving is a separate flow
     * behind a password confirmation.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->can(Ability::MANAGE_USERS) && $user->id !== $target->id;
    }
}
