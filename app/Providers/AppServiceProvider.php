<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\SeoMeta;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Seo\IndexNow;
use App\Seo\Llms;
use App\Seo\Sitemap;
use App\Support\SiteCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Spatie\LaravelSettings\Events\SettingsSaved;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // GD: available on every shared host. Reading applies EXIF orientation.
        $this->app->singleton(ImageManager::class, fn () => new ImageManager(new GdDriver));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every generated URL is https in production. The response cache shares one entry between http and
        // https requests, so a page first built over http would otherwise leak http:// links to everyone.
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // The host maps the domain into a subfolder (public_html/codethepixel/public) with a root .htaccess, so the
        // request root carries that folder and menu links came out as /codethepixel/public/blog. Use APP_URL instead.
        if ($this->app->isProduction() && filled(config('app.url'))) {
            URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
        }

        // CMT-02: per-IP limit for comments (the contact form counts successful sends itself).
        RateLimiter::for('comments', fn (Request $request) => Limit::perMinutes(10, 10)->by('comments|'.$request->ip()));

        // Short, stable type names in polymorphic columns (root_slugs, menu_items).
        Relation::morphMap([
            'page' => Page::class,
            'user' => User::class,
            'category' => Category::class,
            'tag' => Tag::class,
            'series' => Series::class,
            'media' => Media::class,
            'article' => Article::class,
            'seo_meta' => SeoMeta::class,
            'project' => Project::class,
        ]);

        // SEO-05, SEO-08, GEO-01: content changes refresh the sitemaps and llms.txt, and ping IndexNow.
        foreach ([Article::class, Page::class, Project::class, Category::class, Tag::class, Series::class, User::class] as $model) {
            $flush = function (): void {
                Sitemap::flush();
                Llms::flush();
            };
            $model::saved($flush);
            $model::deleted($flush);
        }

        // OPS-04: anything readers can see empties the full-page cache.
        foreach ([Article::class, Page::class, Project::class, Category::class, Tag::class, Series::class, User::class,
            Comment::class, Menu::class, MenuItem::class, Technology::class, Media::class, Redirect::class] as $model) {
            $model::saved(fn () => SiteCache::flush());
            $model::deleted(fn () => SiteCache::flush());
        }
        Event::listen(SettingsSaved::class, fn () => SiteCache::flush());
        foreach ([Article::class, Page::class, Project::class] as $model) {
            $model::saved(fn ($record) => IndexNow::changed($record));
            $model::deleted(fn ($record) => IndexNow::changed($record, deleted: true));
        }
    }
}
