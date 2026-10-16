<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\SeoMeta;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Seo\IndexNow;
use App\Seo\Llms;
use App\Seo\Sitemap;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

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
        foreach ([Article::class, Page::class, Project::class] as $model) {
            $model::saved(fn ($record) => IndexNow::changed($record));
            $model::deleted(fn ($record) => IndexNow::changed($record, deleted: true));
        }
    }
}
