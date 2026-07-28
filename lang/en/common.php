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

    'settings' => [
        'title' => 'Settings',
        'description' => 'Your profile and account options',
        'save' => 'Save',
        'saved' => 'Saved',
        'cancel' => 'Cancel',

        'profile_title' => 'Profile',
        'profile_description' => 'Your name and email address',
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email address',
        'email_placeholder' => 'name@example.com',
        'email_unverified' => 'Your email address is not verified.',
        'email_resend' => 'Resend the verification email',
        'email_sent' => 'A new link has been sent to your email address.',

        'password_title' => 'Change password',
        'password_description' => 'A long password protects an account better than a complicated one',
        'password_current' => 'Current password',
        'password_new' => 'New password',
        'password_confirm' => 'Confirm new password',
        'password_saved' => 'Password updated.',

        'delete_title' => 'Delete account',
        'delete_description' => 'Your account and everything in it will be removed permanently',
        'delete_warning' => 'This cannot be undone',
        'delete_warning_text' => 'Enter your password to confirm deleting your account.',
        'delete_button' => 'Delete account',
        'delete_confirm' => 'Delete this account permanently?',
    ],

    'sessions' => [
        'title' => 'Devices and sessions',
        'description' => 'Where you are signed in. If you see a device you do not recognise, log out everywhere else and change your password.',
        'current' => 'This device',
        'unknown_device' => 'Unknown device',
        'unknown_browser' => 'Unknown browser',
        'unknown_ip' => 'Unknown address',
        'last_active' => 'Last active',
        'logout_others' => 'Log out other devices',
        'logout_others_confirm' => 'Log out of all other devices?',
        'logout_others_explain' => 'This device stays signed in. You will have to log in again elsewhere.',
        'logged_out_others' => 'You have been logged out of all other devices.',
        'only_current' => 'You are signed in on this device only.',
        'cancel' => 'Cancel',
        'password_updated_others_ended' => 'Password updated. Other devices (:count) were signed out.',
    ],

    'nav' => [
        'section' => 'Sections',
        'sessions' => 'Devices',
        'health' => 'Health',
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'profile' => 'Profile',
        'security' => 'Security',
        'locale' => 'Language and time',
    ],

    'errors' => [
        'home' => 'Go home',
        // Every status that has no wording of its own — see
        // bootstrap/app.php. A page rather than whatever the framework
        // would have shown.
        'generic' => [
            'title' => 'That did not work',
            'description' => 'The request did not suit this page. Try going back to the home page.',
        ],
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

    'public' => [
        'sign_in' => 'Sign in',
        'privacy' => 'Privacy',
        'terms' => 'Terms',
    ],

    'legal' => [
        'unfilled' => 'This section has not been filled in.',
        'privacy' => [
            'title' => 'Privacy policy',
            'sections' => [
                'controller' => 'Who processes the data',
                'collected' => 'What we collect',
                'purpose' => 'Why we need it',
                'storage' => 'Where and how long we keep it',
                'sharing' => 'Who we share it with',
                'rights' => 'Your rights',
                'contacts' => 'How to reach us',
            ],
        ],
        'terms' => [
            'title' => 'Terms of use',
            'sections' => [
                'scope' => 'What these terms cover',
                'account' => 'Your account',
                'allowed' => 'What you may do',
                'forbidden' => 'What you may not do',
                'payment' => 'Payment and refunds',
                'liability' => 'Liability',
                'changes' => 'Changes to these terms',
            ],
        ],
    ],

    'health' => [
        'title' => 'Health',
        'description' => 'What is checked and what broke',
        'healthy' => 'All good',
        'unhealthy' => 'Something is wrong',
        'never_ran' => 'The checks have never run. Start the scheduler.',
        'checked_at' => 'Last checked:',
        'status' => [
            'ok' => 'Ok',
            'warning' => 'Warning',
            'failed' => 'Failed',
            'skipped' => 'Skipped',
            'crashed' => 'Crashed',
        ],
    ],

    'pagination' => [
        'showing' => 'Page :page of :pages, :total in all',
        'previous' => 'Back',
        'next' => 'Next',
    ],
    /*
     * Description for search results. Replace all three when cloning —
     * this is deliberately about the starter, not about your product,
     * so that leaving it reads as obviously wrong.
     */
    'seo' => [
        'home' => 'A Laravel and React starter: sign-in, roles, an audit log, three locales.',
        'privacy' => 'How personal data is handled here and what is kept.',
        'terms' => 'The terms this service is used under.',
    ],

];
