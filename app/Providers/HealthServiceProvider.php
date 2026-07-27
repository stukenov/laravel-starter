<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\BackupsCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

/**
 * What "the site is up" actually means for this application.
 *
 * Laravel's own `/up` answers whether PHP can boot. It cannot tell you
 * that the queue stopped, the schedule has not run since Tuesday, or that
 * somebody deployed with APP_DEBUG on — the failures that stay invisible
 * until a person notices something missing.
 *
 * Checks that need a heartbeat (queue, schedule) go red until the worker
 * and the scheduler are actually running. That is the point of them, not
 * a misconfiguration to work around.
 */
class HealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Health::checks([
            // Runs an actual query rather than trusting the connection
            // pool to admit it is broken.
            DatabaseCheck::new(),

            // PostgreSQL has a hard max_connections; leaks show up here
            // long before the site starts refusing to answer.
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),

            CacheCheck::new(),

            // A queue worker that died is silent: jobs pile up and nothing
            // fails. The heartbeat is the only way to notice.
            QueueCheck::new(),

            // Same for the scheduler. Needs `schedule:run` in cron and the
            // heartbeat command in routes/console.php.
            ScheduleCheck::new(),

            // Backups that stopped a month ago look exactly like backups
            // that are working, right until they are needed.
            BackupsCheck::new()
                ->locatedAt(storage_path('app/private/'.config('app.name').'/*.zip'))
                ->numberOfBackups(min: 1),

            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            // These three assert how production is configured, and every
            // one of them is meant to be false in development. Registered
            // everywhere they would report three permanent failures, and a
            // dashboard that is always red is a dashboard nobody reads.
            ...$this->app->isProduction() ? [
                // APP_DEBUG on prints stack traces, environment variables
                // and database credentials to whoever asks.
                DebugModeCheck::new(),
                EnvironmentCheck::new(),
                // Uncached config and routes is a large, silent slowdown.
                OptimizedAppCheck::new(),
            ] : [],
        ]);
    }
}
