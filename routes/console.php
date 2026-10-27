<?php

use App\Models\ContactMessage;
use App\Models\NotFoundLog;
use App\Support\SchedulerHealth;
use App\Support\SiteCache;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Heartbeat for the admin dashboard: proves the cron entry that runs the scheduler is alive.
Schedule::call(fn () => SchedulerHealth::beat())->name('scheduler-heartbeat')->everyMinute();

// ART-02. On shared hosting the scheduler itself runs from cron every minute (ARCH §9).
Schedule::command('ctp:publish-scheduled')->everyMinute()->withoutOverlapping();

// ART-10: daily view totals; raw per-visitor rows are kept for one day only.
Schedule::command('ctp:rollup-views')->dailyAt('00:15')->withoutOverlapping()
    // "Popular this month" and view counts move daily: start the day with fresh pages.
    ->after(fn () => SiteCache::flush());

// ARCH §9: shared hosting has no queue worker. Each minute the scheduler drains the queue
// (image variants, IndexNow, mail) and stops before the next run. Dev has its own queue container.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping()->environments(['production']);

// SEO-11: drop old, rarely hit 404 log rows (scanner noise that slipped through, one-off typos).
Schedule::call(fn () => NotFoundLog::where('last_seen_at', '<', now()->subDays(90))->where('hits', '<', 5)->delete())
    ->name('prune-404-log')->weekly();

// CONTACT-07: contact messages older than 12 months are deleted (PDPA).
Schedule::command('model:prune', ['--model' => [ContactMessage::class]])->daily()->at('03:00');

// SET-03 / OPS-04: the topbar notice turns on and off on schedule; cached pages must follow.
Schedule::call(fn () => SiteCache::watchNotice())->name('notice-window')->everyMinute();

// OPS-03: nightly backups (database + media) to BACKUP_DISKS, old ones cleaned, health checked.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('07:00');
