<?php

return [
    'auth' => [
        'login' => 'Log in',
        'register' => 'Sign up',
        'logout' => 'Log out',
        'forgot_password' => 'Forgot your password?',
        'session_expired' => 'Your session has expired. Please log in again.',
    ],

    'nav' => [
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'profile' => 'Profile',
        'security' => 'Security',
        'locale' => 'Language and time',
    ],

    'errors' => [
        'home' => 'Go home',
        403 => [
            'title' => 'Access denied',
            'description' => 'You do not have permission to view this page.',
        ],
        404 => [
            'title' => 'Page not found',
            'description' => 'The address may be wrong, or the page was removed.',
        ],
        429 => [
            'title' => 'Too many requests',
            'description' => 'Please wait a moment and try again.',
        ],
        500 => [
            'title' => 'Something went wrong',
            'description' => 'We know about the problem and are looking into it.',
        ],
        503 => [
            'title' => 'Under maintenance',
            'description' => 'The site will be back shortly. Please check again later.',
        ],
    ],
];
