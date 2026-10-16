<?php

namespace App\Support;

use App\Settings\NoticeSettings;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;
use Throwable;

// OPS-04: one place to empty the full-page cache when anything visible changes.
class SiteCache
{
    public static function flush(): void
    {
        try {
            ResponseCache::clear();
        } catch (Throwable) {
            // A cache that cannot be cleared must not break saving content.
        }
    }

    // SET-03: the topbar notice has a start and end time; pages cached before then would keep the old state.
    public static function watchNotice(): void
    {
        $visible = app(NoticeSettings::class)->isVisible();
        $previous = Cache::get('site-cache.notice-visible');
        Cache::forever('site-cache.notice-visible', $visible);

        if ($previous !== null && $previous !== $visible) {
            self::flush();
        }
    }
}
