<?php

return [
    'title' => 'Рөлдер',
    'description' => 'Жүйеде кім не істей алады',
    'add' => 'Рөл құру',
    'edit' => 'Рөл баптауы',
    'name' => 'Атауы',
    'name_hint' => 'Латынша, бос орынсыз: editor, moderator',
    'permissions' => 'Құқықтар',
    'permissions_count' => 'Құқық',
    'users_count' => 'Пайдаланушы',
    'protected' => 'Қорғалған',
    'protected_hint' => 'Әкімші рөлін жоюға болмайды: кейін оны қайта беретін ешкім қалмайды.',
    'empty' => 'Әзірге рөл жоқ',
    'save' => 'Сақтау',
    'cancel' => 'Болдырмау',
    'delete' => 'Рөлді жою',
    'created' => 'Рөл құрылды.',
    'updated' => 'Өзгерістер сақталды.',
    'deleted' => 'Рөл жойылды.',
    // Dots are replaced by underscores: a dot in a key makes Laravel
    // read it as a nested path, and the lookup silently returns the key.
    'abilities' => [
        'health_view' => 'Жүйе күйін көру',
        'users_view' => 'Пайдаланушыларды көру',
        'users_manage' => 'Пайдаланушыларды басқару',
        'roles_view' => 'Рөлдерді көру',
        'roles_manage' => 'Рөлдерді басқару',
        'activity_view' => 'Әрекеттер журналын көру',
    ],
];
