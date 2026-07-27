<?php

use Spatie\FailedJobMonitor\Notifiable;
use Spatie\FailedJobMonitor\Notification;

return [

    /*
     * Whether the failed job monitor is enabled. Set to false in your .env
     * (FAILED_JOB_MONITOR_ENABLED=false) to disable notifications.
     */
    'enabled' => env('FAILED_JOB_MONITOR_ENABLED', true),

    /*
     * The notification that will be sent when a job fails.
     */
    'notification' => Notification::class,

    /*
     * The notifiable to which the notification will be sent. The default
     * notifiable will use the mail and slack configuration specified
     * in this config file.
     */
    'notifiable' => Notifiable::class,

    /*
     * By default notifications are sent for all failures. You can pass a callable to filter
     * out certain notifications. The given callable will receive the notification. If the callable
     * return false, the notification will not be sent.
     */
    'notificationFilter' => null,

    /*
     * The channels to which the notification will be sent.
     */
    // Mail only. Slack would need a webhook nobody has configured,
    // and a channel that always fails hides the failure it reports.
    'channels' => explode(',', (string) env('FAILED_JOB_CHANNELS', 'mail')),

    'mail' => [
        // Replace when cloning — see AGENTS.md.
        'to' => explode(',', (string) env('FAILED_JOB_EMAILS', env('MAIL_FROM_ADDRESS', 'hello@example.com'))),
    ],

    'slack' => [
        'webhook_url' => env('FAILED_JOB_SLACK_WEBHOOK_URL'),
    ],
];
