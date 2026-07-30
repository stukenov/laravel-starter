<?php

declare(strict_types=1);

return [
    'title' => 'Roles',
    'description' => 'Who may do what in the system',
    'add' => 'Create role',
    'edit' => 'Role settings',
    'name' => 'Name',
    'name_hint' => 'Latin letters, no spaces: editor, moderator',
    'permissions' => 'Permissions',
    'permissions_count' => 'Permissions',
    'users_count' => 'Users',
    'protected' => 'Protected',
    'protected_hint' => 'The admin role cannot be deleted: nobody would be left able to grant it back.',
    'empty' => 'No roles yet',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete role',
    'delete_confirm' => 'Delete for good?',
    'created' => 'Role created.',
    'updated' => 'Changes saved.',
    'deleted' => 'Role deleted.',
    // Dots are replaced by underscores: a dot in a key makes Laravel
    // read it as a nested path, and the lookup silently returns the key.
    'abilities' => [
        'health_view' => 'See system health',
        'users_view' => 'View users',
        'users_manage' => 'Manage users',
        'roles_view' => 'View roles',
        'roles_manage' => 'Manage roles',
        'activity_view' => 'View the activity log',
    ],
];
