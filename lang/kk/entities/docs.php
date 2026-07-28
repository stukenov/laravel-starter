<?php

return [
    'title' => 'Docs',
    'description' => 'Жазбалар тізімі',
    'search' => 'Іздеу',
    'add' => 'Қосу',
    'edit' => 'Өзгерту',
    'empty' => 'Әзірге жазба жоқ',
    'empty_search' => 'Ештеңе табылмады',
    'save' => 'Сақтау',
    'delete_confirm' => 'Шынымен жою керек пе?',
    'delete' => 'Жою',
    'cancel' => 'Болдырмау',
    'created' => 'Жазба құрылды.',
    'updated' => 'Өзгерістер сақталды.',
    'deleted' => 'Жазба жойылды.',

    /*
     * Field labels live in their own section on purpose.
     *
     * Flat, a field called `title` overwrote the page heading — and
     * `title` is the commonest field name there is, so the generator's
     * own documented example shipped a page headed "Title". A field
     * called `add`, `search` or `empty` would have done the same to its
     * neighbour, silently, because PHP keeps the last key.
     */

    'fields' => [
        'title' => 'Title',
        'attachment' => 'Attachment',
    ],
];
