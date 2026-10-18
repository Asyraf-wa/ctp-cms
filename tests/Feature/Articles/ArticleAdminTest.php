<?php

use App\Content\Revisions;
use App\Enums\ArticleStatus;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Database\Factories\ArticleFactory;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->user = User::factory()->create(['email' => 'owner@example.com', 'name' => 'Asyraf']);
    $this->actingAs($this->user);
    $this->category = Category::factory()->create(['name' => 'CakePHP']);
});

function validArticle(array $overrides = []): array
{
    return [
        'title' => 'CakePHP Bake Basics',
        'slug' => 'cakephp-bake-basics',
        'type' => 'tutorial',
        'author_id' => test()->user->id,
        'category_id' => test()->category->id,
        'status' => 'draft',
        'body' => ArticleFactory::doc([['heading', 'Install'], ['paragraph', 'Run the installer.']]),
        ...$overrides,
    ];
}

it('creates a tutorial with tags and records the first revision', function () {
    $tag = Tag::factory()->create(['name' => 'Bake']);

    Livewire::test(CreateArticle::class)
        // Raw form state: a simple repeater keeps each item as ['point' => …] internally.
        ->fillForm(validArticle(['tags' => [$tag->id], 'tldr' => [['point' => 'Bake generates CRUD code']]]))
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::sole();
    expect($article->title)->toBe('CakePHP Bake Basics')
        ->and($article->status)->toBe(ArticleStatus::Draft)
        ->and($article->tags->pluck('name')->all())->toBe(['Bake'])
        ->and($article->tldr)->toBe(['Bake generates CRUD code']) // stored as a plain list
        ->and($article->toc[0]['id'])->toBe('install')
        ->and($article->revisions()->count())->toBe(1);
});

it('requires a category for tutorials but not for blog posts', function () {
    Livewire::test(CreateArticle::class)->fillForm(validArticle(['category_id' => null]))->call('create')->assertHasFormErrors(['category_id']);
    Livewire::test(CreateArticle::class)->fillForm(validArticle(['category_id' => null, 'type' => 'blog']))->call('create')->assertHasNoFormErrors();
});

it('rejects tutorial slugs that clash with pages or reserved words', function () {
    Page::factory()->create(['title' => 'About']);

    Livewire::test(CreateArticle::class)->fillForm(validArticle(['slug' => 'about']))->call('create')->assertHasFormErrors(['slug']);
    Livewire::test(CreateArticle::class)->fillForm(validArticle(['slug' => 'admin']))->call('create')->assertHasFormErrors(['slug']);
    // A blog post may use the same word: it lives under /blog/.
    Livewire::test(CreateArticle::class)->fillForm(validArticle(['slug' => 'about', 'type' => 'blog']))->call('create')->assertHasNoFormErrors();
});

it('schedules an article published with a future date', function () {
    Livewire::test(CreateArticle::class)
        ->fillForm(validArticle(['status' => 'published', 'published_at' => now()->addDays(2)->toDateTimeString()]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::sole()->status)->toBe(ArticleStatus::Scheduled);
});

it('moves "Last updated" only for meaningful updates', function () {
    $article = Article::factory()->published()->create(['category_id' => $this->category->id, 'author_id' => $this->user->id]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['excerpt' => 'Typo fix'])->call('save')->assertHasNoFormErrors();
    expect($article->fresh()->content_updated_at)->toBeNull();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['excerpt' => 'New section added'])->set('data.meaningful_update', true)->call('save')->assertHasNoFormErrors();
    expect($article->fresh()->content_updated_at)->not->toBeNull();
});

it('keeps the last 20 revisions', function () {
    $article = Article::factory()->create();
    $revisions = app(Revisions::class);

    foreach (range(1, 25) as $i) {
        $article->update(['title' => "Version {$i}"]);
        $revisions->record($article, $this->user);
    }

    expect($article->revisions()->count())->toBe(20)
        ->and($article->revisions()->first()->title)->toBe('Version 25');
});

it('autosaves unsaved work as a revision and offers to restore it', function () {
    $article = Article::factory()->create(['category_id' => $this->category->id, 'author_id' => $this->user->id, 'title' => 'Original']);
    $article->updated_at = now()->subMinute();
    $article->saveQuietly();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['title' => 'Work in progress'])
        ->call('autosave')
        ->assertSet('lastAutosavedAt', fn ($v) => $v !== null);

    expect($article->fresh()->title)->toBe('Original') // the live article is untouched
        ->and(ArticleRevision::where('is_autosave', true)->sole()->title)->toBe('Work in progress');

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionVisible('restoreAutosave')
        ->callAction('restoreAutosave')
        ->assertSchemaStateSet(['title' => 'Work in progress']);
});

it('does not autosave when nothing changed', function () {
    $article = Article::factory()->create(['category_id' => $this->category->id, 'author_id' => $this->user->id, 'tldr' => ['One point', 'Two points']]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])->call('autosave');

    expect(ArticleRevision::where('is_autosave', true)->count())->toBe(0);
});

it('loads an older revision into the editor', function () {
    $article = Article::factory()->create(['category_id' => $this->category->id, 'author_id' => $this->user->id, 'title' => 'First title']);
    $first = app(Revisions::class)->record($article, $this->user);
    $article->update(['title' => 'Second title']);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->callAction('revisions', ['revision_id' => $first->id])
        ->assertSchemaStateSet(['title' => 'First title']);

    expect($article->fresh()->title)->toBe('Second title'); // not saved until the editor clicks Save
});

it('publishes and unpublishes in bulk', function () {
    $articles = Article::factory()->count(2)->create();

    Livewire::test(ListArticles::class)->callTableBulkAction('publish', $articles);
    expect(Article::where('status', 'published')->count())->toBe(2);

    Livewire::test(ListArticles::class)->callTableBulkAction('unpublish', $articles);
    expect(Article::where('status', 'draft')->count())->toBe(2);
});

it('serves published tutorials and blog posts, and hides drafts', function () {
    Article::factory()->published()->create(['title' => 'Live Tutorial', 'category_id' => $this->category->id]);
    Article::factory()->blog()->published()->create(['title' => 'Live Post']);
    Article::factory()->create(['title' => 'Secret Draft']);

    auth()->logout();
    $this->get('/live-tutorial')->assertOk()->assertSee('Live Tutorial')->assertSee('CakePHP');
    $this->get('/blog/live-post')->assertOk()->assertSee('Live Post');
    $this->get('/secret-draft')->assertNotFound();
    $this->get('/live-post')->assertNotFound(); // blog posts are not at the root
});

it('previews drafts only through a signed link', function () {
    $draft = Article::factory()->create(['title' => 'Coming Soon']);

    $this->get(URL::temporarySignedRoute('articles.preview', now()->addHour(), ['article' => $draft]))
        ->assertOk()->assertSee('Coming Soon')->assertSee('noindex, nofollow', false)->assertSee('not public');

    $this->get(route('articles.preview', ['article' => $draft]))->assertForbidden();
});

it('publishes scheduled articles when their time comes', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addMinutes(5)]);
    expect($article->status)->toBe(ArticleStatus::Scheduled);

    $this->travel(6)->minutes();
    $this->artisan('ctp:publish-scheduled')->assertSuccessful();

    expect($article->fresh()->status)->toBe(ArticleStatus::Published);
});

it('re-renders stored HTML on demand', function () {
    $article = Article::factory()->create();
    $article->forceFill(['body_rendered' => 'stale'])->saveQuietly();

    $this->artisan('ctp:rerender')->assertSuccessful();

    expect($article->fresh()->body_rendered)->not->toBe('stale');
});
