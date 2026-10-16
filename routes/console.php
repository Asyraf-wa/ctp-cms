<?php

use App\Models\NotFoundLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ART-02. On shared hosting the scheduler itself runs from cron every minute (ARCH §9).
Schedule::command('ctp:publish-scheduled')->everyMinute()->withoutOverlapping();

// ART-10: daily view totals; raw per-visitor rows are kept for one day only.
Schedule::command('ctp:rollup-views')->dailyAt('00:15')->withoutOverlapping();

// ARCH §9: shared hosting has no queue worker. Each minute the scheduler drains the queue
// (image variants, IndexNow, mail) and stops before the next run. Dev has its own queue container.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping()->environments(['production']);

// SEO-11: drop old, rarely hit 404 log rows (scanner noise that slipped through, one-off typos).
Schedule::call(fn () => NotFoundLog::where('last_seen_at', '<', now()->subDays(90))->where('hits', '<', 5)->delete())
    ->name('prune-404-log')->weekly();
