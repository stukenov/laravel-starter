<?php

return [
    'title' => 'Роли',
    'description' => 'Кто что может делать в системе',
    'add' => 'Создать роль',
    'edit' => 'Настройка роли',
    'name' => 'Название',
    'name_hint' => 'Латиницей, без пробелов: editor, moderator',
    'permissions' => 'Права',
    'permissions_count' => 'Прав',
    'users_count' => 'Пользователей',
    'protected' => 'Защищена',
    'protected_hint' => 'Роль администратора нельзя удалить: иначе её некому будет выдать обратно.',
    'empty' => 'Ролей пока нет',
    'save' => 'Сохранить',
    'cancel' => 'Отмена',
    'delete' => 'Удалить роль',
    'created' => 'Роль создана.',
    'updated' => 'Изменения сохранены.',
    'deleted' => 'Роль удалена.',
    // Dots are replaced by underscores: a dot in a key makes Laravel
    // read it as a nested path, and the lookup silently returns the key.
    'abilities' => [
        'health_view' => 'Видеть состояние системы',
        'users_view' => 'Видеть пользователей',
        'users_manage' => 'Управлять пользователями',
        'roles_view' => 'Видеть роли',
        'roles_manage' => 'Управлять ролями',
        'activity_view' => 'Видеть журнал действий',
    ],
];
