<?php

use App\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Http\Middleware\NormalizeUrl;
use App\Models\Article;
use App\Models\Category;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\User;
use App\Seo\LinkSuggestions;
use App\Seo\SeoChecklist;
use Database\Factories\ArticleFactory;
use Illuminate\Http\Request;
use Livewire\Livewire;

function seoAdmin(): void
{
    config(['ctp.admin_emails' => ['owner@example.com']]);
    test()->actingAs(User::factory()->create(['email' => 'owner@example.com']));
}

it('normalises case, trailing slashes and doubled slashes with a 301, keeping the query', function () {
    $this->get('/Tutorials')->assertRedirect(url('/tutorials'))->assertStatus(301);
    // The test client trims trailing slashes, so run the middleware on raw requests.
    $middleware = new NormalizeUrl;
    $next = fn () => response('ok');
    expect($middleware->handle(Request::create('/tutorials/?sort=popular'), $next)->headers->get('Location'))->toBe(url('/tutorials').'?sort=popular')
        ->and($middleware->handle(Request::create('http://localhost//blog//'), $next)->headers->get('Location'))->toBe(url('/blog'))
        ->and($middleware->handle(Request::create('/storage/media/Photo.WEBP'), $next)->getContent())->toBe('ok');
    $this->get('/admin/login')->assertOk(); // the admin is left alone
    expect($this->post('/Tutorials')->isRedirect())->toBeFalse(); // only GET and HEAD are redirected
});

it('logs 404s with hit counts, skipping scanner noise and the admin', function () {
    $this->get('/old-legacy-post', ['referer' => 'https://forum.example/thread'])->assertNotFound();
    $this->get('/old-legacy-post')->assertNotFound();
    $this->get('/wp-login.php')->assertNotFound();
    $this->get('/.env')->assertNotFound();
    $this->get('/admin/nope')->assertNotFound();

    $log = NotFoundLog::sole();
    expect($log->path)->toBe('/old-legacy-post')
        ->and($log->hits)->toBe(2)
        ->and($log->referrer)->toBe('https://forum.example/thread');
});

it('turns a 404 into a redirect from the log, and manages redirects', function () {
    seoAdmin();
    $this->get('/old-legacy-post');
    $log = NotFoundLog::sole();

    Livewire::test(ListNotFoundLogs::class)
        ->assertCanSeeTableRecords([$log])
        ->callTableAction('redirect', $log, ['status_code' => 301, 'to_path' => '/tutorials'])
        ->assertHasNoTableActionErrors();

    expect(NotFoundLog::count())->toBe(0)->and(Redirect::sole()->to_path)->toBe('/tutorials');
    $this->get('/old-legacy-post')->assertRedirect('/tutorials');

    Livewire::test(ManageRedirects::class)
        ->callAction('create', ['from_path' => '/Gone-Page/', 'status_code' => 410])
        ->assertHasNoActionErrors()
        ->callAction('create', ['from_path' => '/external', 'status_code' => 302, 'to_path' => 'https://github.com/Asyraf-wa'])
        ->assertHasNoActionErrors();

    $this->get('/gone-page')->assertStatus(410);
    $this->get('/external')->assertRedirect('https://github.com/Asyraf-wa')->assertStatus(302);
});

it('checks title, description, slug, headings, alt text, links and length', function () {
    $body = ArticleFactory::doc([['heading', 'Intro', 1], ['paragraph', 'Short.'], ['image', 999]]);

    $checks = collect(SeoChecklist::run('Bake', 'Too short', 'something-else', $body))->keyBy(fn ($c) => strtok($c['label'], ' ('));

    expect($checks->every(fn ($c) => $c['ok'] === false))->toBeTrue();

    $good = ArticleFactory::doc([['heading', 'Install CakePHP'], ['paragraph', str_repeat('word ', 320)]]);
    $good['content'][1]['content'][] = ['type' => 'text', 'text' => 'see more', 'marks' => [['type' => 'link', 'attrs' => ['href' => '/other-tutorial']]]];
    $passed = collect(SeoChecklist::run('Install CakePHP 5 on shared hosting today', str_repeat('Clear description. ', 6), 'install-cakephp-5', $good))->where('ok', true)->count();

    expect($passed)->toBe(8);
});

it('suggests related articles to link to and marks the ones already linked', function () {
    $cake = Category::factory()->create(['name' => 'CakePHP']);
    $tag = Tag::factory()->create(['name' => 'Auth']);
    $tagged = Article::factory()->published()->create(['title' => 'Login With Sessions', 'category_id' => $cake->id]);
    $tagged->tags()->attach($tag);
    $similar = Article::factory()->published()->create(['title' => 'Authentication Plugin Deep Dive']);
    Article::factory()->published()->create(['title' => 'Unrelated Recipe']);

    $body = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => url('/'.$tagged->slug)]]]]]]]];
    $suggestions = LinkSuggestions::for(null, 'CakePHP Authentication Basics', [$tag->id], $cake->id, $body);

    expect($suggestions->pluck('title')->all())->toBe(['Login With Sessions', 'Authentication Plugin Deep Dive'])
        ->and($suggestions->first()['linked'])->toBeTrue()
        ->and($suggestions->last()['linked'])->toBeFalse();
});
