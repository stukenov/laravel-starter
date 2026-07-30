<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

/*
 * RULE(time): the application runs in UTC — AGENTS.md.
 *
 * So does this file. "Daily at 01:00" means 01:00 UTC, which is 06:00 in
 * Almaty — check that against the quiet hours of wherever the project
 * actually runs before trusting it.
 *
 * One cron entry drives all of it:
 *
 *     * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 *
 * Run `php artisan schedule-monitor:sync` after changing anything here, or
 * the monitor keeps watching for a task that no longer exists and reports
 * a failure nobody caused.
 */

// Heartbeats. Each one proves a moving part is still moving: without them
// a dead queue worker and a healthy one look exactly alike.
Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
Schedule::command(DispatchQueueCheckJobsCommand::class)->everyMinute();
Schedule::command(RunHealthChecksCommand::class)
    ->everyMinute()
    // A scheduled command writes its output to /dev/null by default, so a
    // command that throws leaves nothing anywhere. This one sends its
    // notification before it stores its results, so a broken mail
    // transport does not merely lose the alert — it loses the run, and
    // the health page goes on showing the last green result at exactly
    // the moment something is wrong.
    ->onFailure(fn () => Log::error('health:check failed; the health page is now showing a stale result'));

// Cleanup first, so the disk has room for the backup about to be written.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');

// The audit log grows forever otherwise. How long to keep it lives in
// config/activitylog.php — a legal question more than a technical one.
Schedule::command('activitylog:clean')->dailyAt('02:00');

// A week is long enough to look at a failed job and short enough that the
// table does not become the largest one in the database.
Schedule::command('queue:prune-failed --hours=168')->dailyAt('02:30');
Schedule::command('model:prune')->dailyAt('03:00');
