<?php

declare(strict_types=1);

/*
 * RULE(locales): new copy goes into all three files at once — ru, kk and
 * en. A missing key is printed on screen as itself. See AGENTS.md.
 */

return [
    'auth' => [
        'login' => 'Войти',
        'verify_sent' => 'Новая ссылка отправлена на адрес, указанный при регистрации.',
        'password_show' => 'Показать пароль',
        'password_hide' => 'Скрыть пароль',
        'register' => 'Зарегистрироваться',
        'logout' => 'Выйти',
        'forgot_password' => 'Забыли пароль?',
        'session_expired' => 'Сессия истекла. Войдите заново.',

        'email' => 'Электронная почта',
        'email_placeholder' => 'pochta@example.kz',
        'password' => 'Пароль',
        'password_placeholder' => 'Пароль',
        'password_confirm' => 'Повторите пароль',
        'name' => 'Имя',
        'name_placeholder' => 'Имя и фамилия',
        'remember' => 'Запомнить меня',

        'login_title' => 'Вход в аккаунт',
        'login_description' => 'Введите почту и пароль',
        'no_account' => 'Ещё нет аккаунта?',
        'sign_up' => 'Зарегистрироваться',

        'register_title' => 'Создание аккаунта',
        'register_description' => 'Заполните поля, чтобы завести аккаунт',
        'have_account' => 'Уже есть аккаунт?',

        'forgot_title' => 'Восстановление пароля',
        'forgot_description' => 'Пришлём ссылку для смены пароля на почту',
        'send_link' => 'Отправить ссылку',
        'or_return_to' => 'Или вернуться ко',

        'reset_title' => 'Новый пароль',
        'reset_description' => 'Придумайте пароль для входа',
        'reset_submit' => 'Сохранить пароль',

        'verify_title' => 'Подтверждение почты',
        'verify_description' => 'Мы отправили письмо со ссылкой. Перейдите по ней, чтобы подтвердить адрес.',
        'verify_resend' => 'Отправить письмо ещё раз',
        'verify_sent' => 'Новая ссылка отправлена на вашу почту.',

        'confirm_title' => 'Подтвердите пароль',
        'confirm_description' => 'Это защищённый раздел. Введите пароль, чтобы продолжить.',
        'confirm_submit' => 'Подтвердить',
    ],

    'settings' => [
        'avatar' => 'Фотография',
        'avatar_current' => 'Текущая фотография',
        'title' => 'Настройки',
        'description' => 'Профиль и параметры аккаунта',
        'save' => 'Сохранить',
        'saved' => 'Сохранено',
        'cancel' => 'Отмена',

        'profile_title' => 'Профиль',
        'profile_description' => 'Имя и адрес почты',
        'name' => 'Имя',
        'name_placeholder' => 'Имя и фамилия',
        'email' => 'Электронная почта',
        'email_placeholder' => 'pochta@example.kz',
        'email_unverified' => 'Адрес почты не подтверждён.',
        'email_resend' => 'Отправить письмо ещё раз',
        'email_sent' => 'Ссылка отправлена на вашу почту.',

        'password_title' => 'Смена пароля',
        'password_description' => 'Длинный пароль защищает аккаунт лучше сложного',
        'password_current' => 'Текущий пароль',
        'password_new' => 'Новый пароль',
        'password_confirm' => 'Повторите новый пароль',
        'password_saved' => 'Пароль изменён.',

        'delete_title' => 'Удаление аккаунта',
        'delete_description' => 'Аккаунт и все связанные данные будут удалены без возможности восстановления',
        'delete_warning' => 'Это действие необратимо',
        'delete_warning_text' => 'Введите пароль, чтобы подтвердить удаление аккаунта.',
        'delete_button' => 'Удалить аккаунт',
        'delete_confirm' => 'Удалить аккаунт навсегда?',
    ],

    'sessions' => [
        'title' => 'Устройства и сессии',
        'description' => 'Где вы вошли в аккаунт. Если видите незнакомое устройство — выйдите со всех остальных и смените пароль.',
        'current' => 'Текущее устройство',
        'unknown_device' => 'Неизвестное устройство',
        'unknown_browser' => 'Неизвестный браузер',
        'unknown_ip' => 'Адрес неизвестен',
        'last_active' => 'Последняя активность',
        'logout_others' => 'Выйти со всех остальных',
        'logout_others_confirm' => 'Выйти со всех остальных устройств?',
        'logout_others_explain' => 'Текущее устройство останется в системе. На остальных придётся войти заново.',
        'logged_out_others' => 'Вы вышли со всех остальных устройств.',
        'only_current' => 'Вы вошли только с этого устройства.',
        'cancel' => 'Отмена',
        'password_updated_others_ended' => 'Пароль изменён. Остальные устройства (:count) отключены.',
    ],

    'nav' => [
        'section' => 'Разделы',
        'sessions' => 'Устройства',
        'tokens' => 'Токены API',
        'health' => 'Состояние',
        'dashboard' => 'Панель',
        'settings' => 'Настройки',
        'profile' => 'Профиль',
        'security' => 'Безопасность',
        'locale' => 'Язык и время',
    ],

    'errors' => [
        'home' => 'На главную',
        // Every status that has no wording of its own — see
        // bootstrap/app.php. A page rather than whatever the framework
        // would have shown.
        'generic' => [
            'title' => 'Так не получится',
            'description' => 'Запрос не подошёл этой странице. Попробуйте вернуться на главную.',
        ],
        403 => [
            'title' => 'Доступ закрыт',
            'description' => 'У вас нет прав на эту страницу.',
        ],
        404 => [
            'title' => 'Страница не найдена',
            'description' => 'Возможно, адрес указан с ошибкой или страницу удалили.',
        ],
        429 => [
            'title' => 'Слишком много запросов',
            'description' => 'Подождите немного и попробуйте снова.',
        ],
        500 => [
            'title' => 'Что-то пошло не так',
            'description' => 'Мы уже знаем о проблеме и разбираемся.',
        ],
        503 => [
            'title' => 'Идут работы',
            'description' => 'Сайт скоро вернётся. Зайдите чуть позже.',
        ],
    ],

    'public' => [
        'sign_in' => 'Войти',
        'privacy' => 'Конфиденциальность',
        'terms' => 'Условия',
    ],

    'legal' => [
        'unfilled' => 'Раздел не заполнен.',
        'privacy' => [
            'title' => 'Политика конфиденциальности',
            'sections' => [
                'controller' => 'Кто обрабатывает данные',
                'collected' => 'Какие данные собираем',
                'purpose' => 'Зачем они нужны',
                'storage' => 'Где и сколько храним',
                'sharing' => 'Кому передаём',
                'rights' => 'Права пользователя',
                'contacts' => 'Как с нами связаться',
            ],
        ],
        'terms' => [
            'title' => 'Условия использования',
            'sections' => [
                'scope' => 'На что распространяются условия',
                'account' => 'Учётная запись',
                'allowed' => 'Что можно',
                'forbidden' => 'Что нельзя',
                'payment' => 'Оплата и возврат',
                'liability' => 'Ответственность',
                'changes' => 'Изменение условий',
            ],
        ],
    ],

    'health' => [
        'title' => 'Состояние',
        'description' => 'Что проверяется и что сломалось',
        'healthy' => 'Всё работает',
        'degraded' => 'Работает с замечаниями',
        'unhealthy' => 'Есть проблемы',
        'never_ran' => 'Проверки ещё ни разу не выполнялись. Запустите планировщик.',
        'checked_at' => 'Последняя проверка:',
        'status' => [
            'ok' => 'Норма',
            'warning' => 'Предупреждение',
            'failed' => 'Сбой',
            'skipped' => 'Пропущена',
            'crashed' => 'Упала',
        ],
    ],

    'pagination' => [
        'showing' => 'Страница :page из :pages, всего :total',
        'previous' => 'Назад',
        'next' => 'Дальше',
    ],
    /*
     * Description for search results. Replace all three when cloning —
     * this is deliberately about the starter, not about your product,
     * so that leaving it reads as obviously wrong.
     */
    'seo' => [
        'home' => 'Стартовый набор на Laravel и React: вход, роли, журнал действий, три языка.',
        'privacy' => 'Как мы обращаемся с персональными данными и что храним.',
        'terms' => 'Условия использования сервиса.',
    ],

    'tokens' => [
        'title' => 'Токены API',
        'description' => 'Ключи для программного доступа. Токен — это пароль, у которого нет срока: выдавайте по одному на приложение и отзывайте, когда оно больше не нужно.',
        'name' => 'Название',
        'name_hint' => 'Чтобы потом понять, какой отзывать.',
        'create' => 'Создать токен',
        'created' => 'Токен создан.',
        'revoke' => 'Отозвать',
        'revoke_confirm' => 'Точно отозвать?',
        'revoked' => 'Токен отозван.',
        'empty' => 'Токенов пока нет.',
        'last_used' => 'Использован',
        'never_used' => 'Ни разу',
        'copy_once' => 'Скопируйте сейчас — второй раз он не покажется.',
        'copy' => 'Скопировать',
        'copied' => 'Скопировано',
    ],
];
