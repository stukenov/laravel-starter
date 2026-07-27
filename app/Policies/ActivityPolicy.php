<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use App\Support\Access\Ability;

/**
 * RULE(access): the log shows who did what to whom, so reading it is a
 * permission of its own — AGENTS.md.
 *
 * There is deliberately no create, update or delete. An audit log that can
 * be edited is not an audit log; entries are written by the application and
 * removed only by the retention command.
 */
class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::VIEW_ACTIVITY);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can(Ability::VIEW_ACTIVITY);
    }
}
