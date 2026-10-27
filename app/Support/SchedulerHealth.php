<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * On shared hosting a single cron entry runs `schedule:run` every minute, and that drives scheduled posts, the mail
 * and image queue, roll-ups and backups. The scheduler leaves a heartbeat so the admin can see it is alive without a Terminal.
 */
class SchedulerHealth
{
    private const KEY = 'ctp.scheduler.heartbeat';

    // Cron fires every minute; three missed minutes means it has stopped.
    private const STALE_AFTER_MINUTES = 3;

    public static function beat(): void
    {
        Cache::forever(self::KEY, now()->getTimestamp());
    }

    public static function lastRun(): ?CarbonImmutable
    {
        $timestamp = Cache::get(self::KEY);

        return is_int($timestamp) ? CarbonImmutable::createFromTimestamp($timestamp) : null;
    }

    public static function isRunning(): bool
    {
        $last = self::lastRun();

        return $last !== null && $last->diffInMinutes(now(), absolute: true) < self::STALE_AFTER_MINUTES;
    }

    /** @return array{waiting: int, failed: int} */
    public static function queue(): array
    {
        return [
            'waiting' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    }
}
