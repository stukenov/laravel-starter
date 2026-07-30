<?php

declare(strict_types=1);

return [
    'auth' => [
        'login' => 'Кіру',
        'verify_sent' => 'Тіркелу кезінде көрсетілген мекенжайға жаңа сілтеме жіберілді.',
        'password_show' => 'Құпия сөзді көрсету',
        'password_hide' => 'Құпия сөзді жасыру',
        'register' => 'Тіркелу',
        'logout' => 'Шығу',
        'forgot_password' => 'Құпиясөзді ұмыттыңыз ба?',
        'session_expired' => 'Сеанс мерзімі бітті. Қайта кіріңіз.',

        'email' => 'Электрондық пошта',
        'email_placeholder' => 'pochta@example.kz',
        'password' => 'Құпиясөз',
        'password_placeholder' => 'Құпиясөз',
        'password_confirm' => 'Құпиясөзді қайталаңыз',
        'name' => 'Аты',
        'name_placeholder' => 'Аты-жөні',
        'remember' => 'Мені есте сақта',

        'login_title' => 'Аккаунтқа кіру',
        'login_description' => 'Пошта мен құпиясөзді енгізіңіз',
        'no_account' => 'Аккаунтыңыз жоқ па?',
        'sign_up' => 'Тіркелу',

        'register_title' => 'Аккаунт құру',
        'register_description' => 'Аккаунт ашу үшін өрістерді толтырыңыз',
        'have_account' => 'Аккаунтыңыз бар ма?',

        'forgot_title' => 'Құпиясөзді қалпына келтіру',
        'forgot_description' => 'Поштаға құпиясөзді ауыстыру сілтемесін жібереміз',
        'send_link' => 'Сілтеме жіберу',
        'or_return_to' => 'Немесе оралыңыз',

        'reset_title' => 'Жаңа құпиясөз',
        'reset_description' => 'Кіру үшін құпиясөз ойлап табыңыз',
        'reset_submit' => 'Құпиясөзді сақтау',

        'verify_title' => 'Поштаны растау',
        'verify_description' => 'Сілтемесі бар хат жібердік. Мекенжайды растау үшін сілтемеге өтіңіз.',
        'verify_resend' => 'Хатты қайта жіберу',
        'verify_sent' => 'Жаңа сілтеме поштаңызға жіберілді.',

        'confirm_title' => 'Құпиясөзді растаңыз',
        'confirm_description' => 'Бұл қорғалған бөлім. Жалғастыру үшін құпиясөзді енгізіңіз.',
        'confirm_submit' => 'Растау',
    ],

    'settings' => [
        'avatar' => 'Фотосурет',
        'avatar_current' => 'Ағымдағы фотосурет',
        'title' => 'Баптаулар',
        'description' => 'Профиль және аккаунт параметрлері',
        'save' => 'Сақтау',
        'saved' => 'Сақталды',
        'cancel' => 'Болдырмау',

        'profile_title' => 'Профиль',
        'profile_description' => 'Аты және пошта мекенжайы',
        'name' => 'Аты',
        'name_placeholder' => 'Аты-жөні',
        'email' => 'Электрондық пошта',
        'email_placeholder' => 'pochta@example.kz',
        'email_unverified' => 'Пошта мекенжайы расталмаған.',
        'email_resend' => 'Хатты қайта жіберу',
        'email_sent' => 'Сілтеме поштаңызға жіберілді.',

        'password_title' => 'Құпиясөзді ауыстыру',
        'password_description' => 'Ұзын құпиясөз аккаунтты күрделіден жақсы қорғайды',
        'password_current' => 'Ағымдағы құпиясөз',
        'password_new' => 'Жаңа құпиясөз',
        'password_confirm' => 'Жаңа құпиясөзді қайталаңыз',
        'password_saved' => 'Құпиясөз өзгертілді.',

        'delete_title' => 'Аккаунтты жою',
        'delete_description' => 'Аккаунт және оған байланысты барлық деректер қалпына келтірусіз жойылады',
        'delete_warning' => 'Бұл әрекет қайтарылмайды',
        'delete_warning_text' => 'Аккаунтты жоюды растау үшін құпиясөзді енгізіңіз.',
        'delete_button' => 'Аккаунтты жою',
        'delete_confirm' => 'Аккаунт біржола жойылсын ба?',
    ],

    'sessions' => [
        'title' => 'Құрылғылар мен сеанстар',
        'description' => 'Аккаунтқа қай жерден кіргеніңіз. Бейтаныс құрылғы көрсеңіз — қалғандарынан шығып, құпиясөзді ауыстырыңыз.',
        'current' => 'Ағымдағы құрылғы',
        'unknown_device' => 'Белгісіз құрылғы',
        'unknown_browser' => 'Белгісіз браузер',
        'unknown_ip' => 'Мекенжай белгісіз',
        'last_active' => 'Соңғы белсенділік',
        'logout_others' => 'Қалған құрылғылардан шығу',
        'logout_others_confirm' => 'Қалған барлық құрылғылардан шығу керек пе?',
        'logout_others_explain' => 'Ағымдағы құрылғы жүйеде қалады. Қалғандарына қайта кіру керек болады.',
        'logged_out_others' => 'Қалған барлық құрылғылардан шықтыңыз.',
        'only_current' => 'Сіз тек осы құрылғыдан кіргенсіз.',
        'cancel' => 'Болдырмау',
        'password_updated_others_ended' => 'Құпия сөз өзгертілді. Қалған құрылғылар (:count) ажыратылды.',
    ],

    'nav' => [
        'section' => 'Бөлімдер',
        'sessions' => 'Құрылғылар',
        'tokens' => 'API токендері',
        'health' => 'Күй',
        'dashboard' => 'Басқару тақтасы',
        'settings' => 'Баптаулар',
        'profile' => 'Профиль',
        'security' => 'Қауіпсіздік',
        'locale' => 'Тіл және уақыт',
    ],

    'errors' => [
        'home' => 'Басты бетке',
        // Every status that has no wording of its own — see
        // bootstrap/app.php. A page rather than whatever the framework
        // would have shown.
        'generic' => [
            'title' => 'Бұлай болмайды',
            'description' => 'Сұрау бұл бетке келмеді. Басты бетке оралып көріңіз.',
        ],
        403 => [
            'title' => 'Қолжетімсіз',
            'description' => 'Бұл бетке кіру құқығыңыз жоқ.',
        ],
        404 => [
            'title' => 'Бет табылмады',
            'description' => 'Мекенжай қате болуы мүмкін немесе бет жойылған.',
        ],
        429 => [
            'title' => 'Сұраныс тым көп',
            'description' => 'Сәл күтіп, қайта көріңіз.',
        ],
        500 => [
            'title' => 'Бірдеңе дұрыс болмады',
            'description' => 'Мәселе туралы білеміз, шешіп жатырмыз.',
        ],
        503 => [
            'title' => 'Техникалық жұмыстар',
            'description' => 'Сайт жақында оралады. Кейінірек кіріңіз.',
        ],
    ],

    'public' => [
        'sign_in' => 'Кіру',
        'privacy' => 'Құпиялылық',
        'terms' => 'Шарттар',
    ],

    'legal' => [
        'unfilled' => 'Бөлім толтырылмаған.',
        'privacy' => [
            'title' => 'Құпиялылық саясаты',
            'sections' => [
                'controller' => 'Деректерді кім өңдейді',
                'collected' => 'Қандай деректер жинаймыз',
                'purpose' => 'Олар не үшін қажет',
                'storage' => 'Қайда және қанша сақтаймыз',
                'sharing' => 'Кімге береміз',
                'rights' => 'Пайдаланушы құқықтары',
                'contacts' => 'Бізбен қалай байланысуға болады',
            ],
        ],
        'terms' => [
            'title' => 'Пайдалану шарттары',
            'sections' => [
                'scope' => 'Шарттар неге қолданылады',
                'account' => 'Тіркелгі',
                'allowed' => 'Не істеуге болады',
                'forbidden' => 'Не істеуге болмайды',
                'payment' => 'Төлем және қайтару',
                'liability' => 'Жауапкершілік',
                'changes' => 'Шарттарды өзгерту',
            ],
        ],
    ],

    'health' => [
        'title' => 'Күй',
        'description' => 'Не тексеріледі және не бұзылған',
        'healthy' => 'Бәрі жұмыс істейді',
        'degraded' => 'Ескертулермен жұмыс істейді',
        'unhealthy' => 'Мәселелер бар',
        'never_ran' => 'Тексерулер әлі орындалған жоқ. Жоспарлаушыны қосыңыз.',
        'checked_at' => 'Соңғы тексеру:',
        'status' => [
            'ok' => 'Қалыпты',
            'warning' => 'Ескерту',
            'failed' => 'Ақау',
            'skipped' => 'Өткізілді',
            'crashed' => 'Құлады',
        ],
    ],

    'pagination' => [
        'showing' => ':pages ішінен :page бет, барлығы :total',
        'previous' => 'Артқа',
        'next' => 'Әрі қарай',
    ],
    /*
     * Description for search results. Replace all three when cloning —
     * this is deliberately about the starter, not about your product,
     * so that leaving it reads as obviously wrong.
     */
    'seo' => [
        'home' => 'Laravel мен React негізіндегі бастапқы жинақ: кіру, рөлдер, әрекеттер журналы, үш тіл.',
        'privacy' => 'Дербес деректерді қалай өңдейміз және нені сақтаймыз.',
        'terms' => 'Қызметті пайдалану шарттары.',
    ],

    'tokens' => [
        'title' => 'API токендері',
        'description' => 'Бағдарламалық қатынас кілттері. Токен — мерзімі жоқ құпия сөз: әр қолданбаға біреуін беріңіз де, қажеті болмаса қайтарып алыңыз.',
        'name' => 'Атауы',
        'name_hint' => 'Кейін қайсысын алу керегін білу үшін.',
        'create' => 'Токен жасау',
        'created' => 'Токен жасалды.',
        'revoke' => 'Қайтарып алу',
        'revoke_confirm' => 'Шынымен қайтарып алу керек пе?',
        'revoked' => 'Токен қайтарып алынды.',
        'empty' => 'Әзірге токендер жоқ.',
        'last_used' => 'Қолданылған',
        'never_used' => 'Бірде-бір рет',
        'copy_once' => 'Қазір көшіріп алыңыз — екінші рет көрсетілмейді.',
        'copy' => 'Көшіру',
        'copied' => 'Көшірілді',
    ],
];
