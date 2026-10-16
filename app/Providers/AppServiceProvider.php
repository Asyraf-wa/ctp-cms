<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        ]);
    }
}
