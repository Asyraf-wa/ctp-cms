<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Site\ArchiveController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\AuthorController;
use App\Http\Controllers\Site\EngagementController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\OgImageController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\RootSlugController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\TutorialController;
use Illuminate\Support\Facades\Route;

$slug = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::get('/', HomeController::class)->name('home');
Route::get('tutorials', [TutorialController::class, 'index'])->name('tutorials.index');
Route::get('category/{slug}', [ArchiveController::class, 'category'])->where('slug', $slug)->name('categories.show');
Route::get('tag/{slug}', [ArchiveController::class, 'tag'])->where('slug', $slug)->name('tags.show');
Route::get('series', [ArchiveController::class, 'seriesIndex'])->name('series.index');
Route::get('series/{slug}', [ArchiveController::class, 'series'])->where('slug', $slug)->name('series.show');
Route::get('blog', [ArticleController::class, 'blogIndex'])->name('blog.index');
Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('projects/{slug}', [ProjectController::class, 'show'])->where('slug', $slug)->name('projects.show');
Route::get('og/{key}.png', OgImageController::class)->where('key', '[a-z]+-\d+-[a-f0-9]{8}')->name('og');
Route::get('search', [SearchController::class, 'page'])->name('search');
Route::get('api/search', [SearchController::class, 'api'])->middleware('throttle:60,1')->name('api.search');
// ART-10, ART-12. No CSRF token (pages are cached); the controller checks origin and filters bots.
Route::middleware('throttle:30,1')->group(function () {
    Route::post('api/articles/{article}/view', [EngagementController::class, 'view'])->whereNumber('article')->name('api.articles.view');
    Route::post('api/articles/{article}/kudos', [EngagementController::class, 'kudos'])->whereNumber('article')->name('api.articles.kudos');
});
Route::get('author/{slug}', [AuthorController::class, 'show'])->where('slug', $slug)->name('authors.show');

Route::middleware('throttle:10,1')->prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
    Route::get('callback', [GoogleAuthController::class, 'callback'])->name('callback');
});

Route::get('blog/{slug}', [ArticleController::class, 'blog'])->where('slug', $slug)->name('blog.show');

// The sample that became the home page (D20).
Route::redirect('preview/home', '/', 301);

// ART-04: signed preview links from the admin; never indexed.
Route::get('preview/articles/{article}', [ArticleController::class, 'preview'])
    ->middleware('signed')
    ->withTrashed()
    ->name('articles.preview');
Route::get('preview/pages/{page}', [PageController::class, 'preview'])
    ->middleware('signed')
    ->name('pages.preview');

// D9: tutorials and pages at domain/{slug}. Must stay the LAST route; every other first segment is reserved.
Route::get('{slug}', RootSlugController::class)
    ->where('slug', $slug)
    ->name('root-slug');
