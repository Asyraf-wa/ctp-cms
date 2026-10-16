<?php

namespace App\Seo;

use App\Jobs\SubmitToIndexNow;
use App\Models\Contracts\HasPublicPath;
use App\Settings\IntegrationSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * SEO-08: tell Bing, Yandex, Naver, Seznam… about new, changed and removed URLs.
 * Production only, with a key from Site settings → Integrations. One ping per URL per 10 minutes.
 */
class IndexNow
{
    public static function enabled(): bool
    {
        return config('ctp.seo.indexnow') && filled(app(IntegrationSettings::class)->indexnow_key);
    }

    // After a save or delete: published content, or content that was public until this change.
    public static function changed(Model $model, bool $deleted = false): void
    {
        if (! $model instanceof HasPublicPath || ! self::enabled()) {
            return;
        }

        $wasPublic = $model->wasRecentlyCreated ? false : self::wasPublic($model);
        if (! $deleted && ! $model->isPubliclyVisible() && ! $wasPublic) {
            return;
        }

        $urls = array_filter([
            $model->publicPath() ? url($model->publicPath()) : null,
            // A renamed slug: the old URL now redirects.
            $model->wasChanged('slug') && $model->getOriginal('slug') ? url((string) $model->pathForSlug($model->getOriginal('slug'))) : null,
        ]);

        $fresh = array_values(array_filter($urls, fn (string $url) => Cache::add('indexnow.'.md5($url), true, now()->addMinutes(10))));
        if ($fresh) {
            SubmitToIndexNow::dispatch($fresh);
        }
    }

    private static function wasPublic(Model $model): bool
    {
        $original = $model->replicate();
        $original->setRawAttributes($model->getRawOriginal());

        return $original instanceof HasPublicPath && $original->isPubliclyVisible();
    }
}
