<?php

namespace App\Policies;

use App\Models\Doc;
use App\Models\User;
use App\Support\Access\Ability;

/**
 * RULE(access): gated by permissions — AGENTS.md.
 *
 * Registration is open by default, so a policy returning true for every
 * verb hands the panel to anyone who signs up. Add the two constants to
 * The generator adds both constants to App\Support\Access\Ability and
 * RoleSeeder grants them to the admin role on the next deploy.
 */
class DocPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::VIEW_DOCS);
    }

    public function view(User $user, Doc $doc): bool
    {
        return $user->can(Ability::VIEW_DOCS);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::MANAGE_DOCS);
    }

    public function update(User $user, Doc $doc): bool
    {
        return $user->can(Ability::MANAGE_DOCS);
    }

    public function delete(User $user, Doc $doc): bool
    {
        return $user->can(Ability::MANAGE_DOCS);
    }
}
