<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\RootSlugController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
});

Route::middleware('throttle:10,1')->prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
    Route::get('callback', [GoogleAuthController::class, 'callback'])->name('callback');
});

Route::get('blog/{slug}', [ArticleController::class, 'blog'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('blog.show');

// ART-04: signed preview links from the admin; never indexed.
Route::get('preview/articles/{article}', [ArticleController::class, 'preview'])
    ->middleware('signed')
    ->withTrashed()
    ->name('articles.preview');

// D9: tutorials and pages at domain/{slug}. Must stay the LAST route; every other first segment is reserved.
Route::get('{slug}', RootSlugController::class)
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('root-slug');
