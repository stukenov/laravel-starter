<?php

namespace App\Policies;

use App\Models\User;

/**
 * ПРАВИЛО(конвенции): политика называется по сущности — AGENTS.md.
 *
 * Ролей в стартере ещё нет — они появятся в этапе 3 вместе с
 * spatie/laravel-permission. До тех пор доступ есть у любого вошедшего,
 * но точка расширения уже на месте: менять придётся один файл.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $target): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $target): bool
    {
        return true;
    }

    /**
     * Удалить себя нельзя — иначе администратор одним кликом лишает
     * систему последнего входа. Для ухода есть отдельный экран удаления
     * аккаунта, там оно осознанное и с подтверждением пароля.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id;
    }
}
