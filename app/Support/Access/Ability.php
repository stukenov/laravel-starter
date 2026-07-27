<?php

namespace App\Support\Access;

/**
 * RULE(access): permissions are declared in code, roles are data.
 *
 * A permission means something only because code checks it, so inventing
 * one through the UI produces a name nothing enforces. Roles are the other
 * way round: which permissions a role carries is a business decision and
 * belongs in the panel.
 *
 * See AGENTS.md.
 */
final class Ability
{
    public const VIEW_USERS = 'users.view';

    public const MANAGE_USERS = 'users.manage';

    public const VIEW_ROLES = 'roles.view';

    public const MANAGE_ROLES = 'roles.manage';

    public const VIEW_ACTIVITY = 'activity.view';

    public const VIEW_HEALTH = 'health.view';

    public const VIEW_WIDGETS = 'widgets.view';

    public const MANAGE_WIDGETS = 'widgets.manage';
    public const VIEW_TAGS = 'tags.view';

    public const MANAGE_TAGS = 'tags.manage';
    public const VIEW_NOTES = 'notes.view';

    public const MANAGE_NOTES = 'notes.manage';



    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::VIEW_USERS,
            self::MANAGE_USERS,
            self::VIEW_ROLES,
            self::MANAGE_ROLES,
            self::VIEW_ACTIVITY,
            self::VIEW_HEALTH,
            self::VIEW_WIDGETS,
            self::MANAGE_WIDGETS,
            self::VIEW_TAGS,
            self::MANAGE_TAGS,
            self::VIEW_NOTES,
            self::MANAGE_NOTES,
        ];
    }
}
