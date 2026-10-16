<?php

use App\Legacy\LegacyImporter;
use App\Models\Article;
use App\Models\Category;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Runs against the real legacy dump (./dev legacy-import). Skipped where it is not loaded, e.g. CI.
beforeEach(function () {
    config(['database.connections.legacy' => ['database' => config('ctp.legacy_database')] + config('database.connections.mysql')]);
    try {
        $loaded = DB::connection('legacy')->table('articles')->count() > 0;
    } catch (Throwable) {
        $loaded = false;
    }
    if (! $loaded) {
        $this->markTestSkipped('The legacy dump is not loaded (./dev legacy-import).');
    }
    User::factory()->create();
});

it('imports tutorials with headings, code blocks, tags, SEO and redirects, and skips blog posts', function () {
    $report = app(LegacyImporter::class)->run(downloads: false, only: [3, 14, 45, 52]);

    expect($report->errors)->toBe([])
        ->and(Article::count())->toBe(3)
        ->and($report->skipped)->toHaveCount(1);

    $crud = Article::where('legacy_id', 3)->sole();
    expect($crud->slug)->toBe('cakephp-4-crud')
        ->and($crud->category->name)->toBe('CakePHP')
        ->and($crud->toc[0]['label'])->toBe('Database configuration')
        ->and($crud->needs_review)->toBeTrue() // its screenshots are gone (D11)
        ->and($crud->body_rendered)->toContain('Screenshot unavailable')->toContain('terminal')
        ->and($crud->views)->toBeGreaterThan(0)
        ->and($crud->technologies->first()?->pivot->version)->toBe('4.x')
        ->and($crud->published_at->timezone('Asia/Kuala_Lumpur')->format('H:i'))->toBe('09:00');

    expect(Article::where('legacy_id', 14)->sole()->body_rendered)->toContain('config/bootstrap.php');
    expect(Article::where('legacy_id', 45)->sole()->body_rendered)->toContain('pen-embed');
    expect(Category::whereNotNull('legacy_id')->count())->toBe(5);

    $this->get('/codethepixel/articles/CakePHP-4-CRUD')->assertRedirect('/cakephp-4-crud');
    $this->get('/codethepixel/articles/This-is-My-First-Blog-Post')->assertStatus(410);
    expect(Redirect::where('source', 'legacy')->count())->toBeGreaterThan(8);
});

it('leaves already imported articles alone unless forced', function () {
    app(LegacyImporter::class)->run(downloads: false, only: [3]);
    Article::where('legacy_id', 3)->update(['title' => 'Edited By Hand']);

    app(LegacyImporter::class)->run(downloads: false, only: [3]);
    expect(Article::where('legacy_id', 3)->value('title'))->toBe('Edited By Hand');

    app(LegacyImporter::class)->run(downloads: false, force: true, only: [3]);
    expect(Article::where('legacy_id', 3)->value('title'))->toBe('CakePHP 4 CRUD');
});

it('redirects old listing URLs by their query string', function () {
    app(LegacyImporter::class)->run(downloads: false, only: [3]);

    $this->get('/codethepixel/articles?tag=cakephp')->assertRedirect('/tag/cakephp');
    $this->get('/codethepixel/articles?category=1')->assertRedirect('/category/cakephp');
    $this->get('/codethepixel?page=4')->assertRedirect('/');
});
