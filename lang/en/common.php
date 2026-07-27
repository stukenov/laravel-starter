<?php

return [
    'auth' => [
        'login' => 'Log in',
        'register' => 'Sign up',
        'logout' => 'Log out',
        'forgot_password' => 'Forgot your password?',
        'session_expired' => 'Your session has expired. Please log in again.',

        'email' => 'Email address',
        'email_placeholder' => 'name@example.com',
        'password' => 'Password',
        'password_placeholder' => 'Password',
        'password_confirm' => 'Confirm password',
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'remember' => 'Remember me',

        'login_title' => 'Log in to your account',
        'login_description' => 'Enter your email and password below',
        'no_account' => "Don't have an account?",
        'sign_up' => 'Sign up',

        'register_title' => 'Create an account',
        'register_description' => 'Fill in the fields below to get started',
        'have_account' => 'Already have an account?',

        'forgot_title' => 'Reset your password',
        'forgot_description' => "We'll email you a link to choose a new password",
        'send_link' => 'Email password reset link',
        'or_return_to' => 'Or, return to',

        'reset_title' => 'New password',
        'reset_description' => 'Choose a password to log in with',
        'reset_submit' => 'Save password',

        'verify_title' => 'Verify your email',
        'verify_description' => 'We sent you a link. Follow it to confirm your address.',
        'verify_resend' => 'Resend verification email',
        'verify_sent' => 'A new link has been sent to your email address.',

        'confirm_title' => 'Confirm your password',
        'confirm_description' => 'This is a secure area. Enter your password to continue.',
        'confirm_submit' => 'Confirm',
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
