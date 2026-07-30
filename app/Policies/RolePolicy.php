<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Support\Access\Ability;
use Database\Seeders\RoleSeeder;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::VIEW_ROLES);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(Ability::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::MANAGE_ROLES);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(Ability::MANAGE_ROLES);
    }

    /**
     * The admin role cannot be deleted at all. Removing it leaves nobody
     * able to grant it back, and the panel locks everyone out for good.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can(Ability::MANAGE_ROLES) && $role->name !== RoleSeeder::ADMIN;
    }
}
