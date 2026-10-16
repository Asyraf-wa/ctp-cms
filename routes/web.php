<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Seo\FeedController;
use App\Http\Controllers\Seo\IndexNowKeyController;
use App\Http\Controllers\Seo\MarkdownController;
use App\Http\Controllers\Seo\RobotsController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\Site\ArchiveController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\AuthorController;
use App\Http\Controllers\Site\CommentController;
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
// SEO-05..08: sitemaps, robots.txt, feeds and the IndexNow key file.
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('sitemaps/{section}.xml', [SitemapController::class, 'section'])->where('section', '[a-z]+')->name('sitemap.section');
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('feed', [FeedController::class, 'all'])->name('feed');
Route::get('feed/atom', [FeedController::class, 'atom'])->name('feed.atom');
Route::get('feed/blog', [FeedController::class, 'blog'])->name('feed.blog');
Route::get('feed/category/{slug}', [FeedController::class, 'category'])->where('slug', $slug)->name('feed.category');
// GEO-01, GEO-02 (before the IndexNow key route: "llms-full" also looks like a key).
Route::get('llms.txt', [MarkdownController::class, 'index'])->name('llms');
Route::get('llms-full.txt', [MarkdownController::class, 'full'])->name('llms.full');
Route::get('blog/{slug}.md', [MarkdownController::class, 'blog'])->where('slug', $slug)->name('blog.markdown');
Route::get('{slug}.md', [MarkdownController::class, 'root'])->where('slug', $slug)->name('root-slug.markdown');
Route::get('{key}.txt', IndexNowKeyController::class)->where('key', '[A-Za-z0-9-]{8,128}')->name('indexnow.key');

Route::get('og/{key}.png', OgImageController::class)->where('key', '[a-z]+-\d+-[a-f0-9]{8}')->name('og');
Route::get('search', [SearchController::class, 'page'])->name('search');
Route::get('api/search', [SearchController::class, 'api'])->middleware('throttle:60,1')->name('api.search');
// ART-10, ART-12. No CSRF token (pages are cached); the controller checks origin and filters bots.
Route::middleware('throttle:30,1')->group(function () {
    Route::post('api/articles/{article}/view', [EngagementController::class, 'view'])->whereNumber('article')->name('api.articles.view');
    Route::post('api/articles/{article}/kudos', [EngagementController::class, 'kudos'])->whereNumber('article')->name('api.articles.kudos');
});
// CMT-01, CMT-02: same no-CSRF reasoning as above; stricter limit.
Route::post('api/articles/{article}/comments', [CommentController::class, 'store'])->whereNumber('article')->middleware('throttle:comments')->name('api.articles.comments');
Route::get('comments/unsubscribe/{token}', [CommentController::class, 'unsubscribe'])->where('token', '[a-z0-9]{40}')->name('comments.unsubscribe');
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
