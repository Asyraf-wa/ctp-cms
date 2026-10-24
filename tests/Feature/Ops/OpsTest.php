<?php

use App\Filament\Widgets\NeedsReview;
use App\Filament\Widgets\PopularArticles;
use App\Filament\Widgets\RecentActivity;
use App\Filament\Widgets\SiteOverview;
use App\Http\Cache\CookielessSerializer;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Settings\NoticeSettings;
use App\Support\SiteCache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    config(['responsecache.enabled' => true]);
    $this->article = Article::factory()->published()->create(['title' => 'Bake Basics', 'category_id' => Category::factory()->create()->id]);
});

it('serves guests a cached copy without anyone else\'s cookies, until content changes', function () {
    $first = $this->get('/tutorials')->assertOk()->assertSee('Bake Basics');
    expect($first->headers->getCookies())->not->toBeEmpty();

    // A change that bypasses model events: the cached page still shows the old title.
    DB::table('articles')->where('id', $this->article->id)->update(['title' => 'Renamed Quietly']);
    $cached = $this->get('/tutorials')->assertOk()->assertSee('Bake Basics');
    expect($cached->getContent())->toBe($first->getContent());

    // What goes into the cache has no Set-Cookie: the first visitor's session must never be replayed.
    $response = response('page');
    $response->headers->setCookie(cookie('code-the-pixel-session', 'secret-session-id'));
    expect((new CookielessSerializer)->serialize($response))->not->toContain('secret-session-id')
        ->and($response->headers->getCookies())->toHaveCount(1); // the live response keeps its cookie

    // Saving through the model clears the cache.
    $this->article->refresh()->update(['title' => 'Renamed Properly']);
    $this->get('/tutorials')->assertSee('Renamed Properly');
});

it('never caches signed-in users, unknown query strings or posts', function () {
    $this->get('/tutorials?unexpected=1')->assertOk();
    DB::table('articles')->update(['title' => 'Changed']);
    $this->get('/tutorials?unexpected=1')->assertSee('Changed');

    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
    $this->get('/blog')->assertOk();
    DB::table('articles')->update(['title' => 'Changed Again']);
    $this->get('/tutorials')->assertSee('Changed Again');
});

it('clears cached pages when the topbar notice switches on or off', function () {
    updateSettings(NoticeSettings::class, ['enabled' => true, 'message' => 'Maintenance tonight', 'starts_at' => now()->addMinutes(5)->toDateTimeString(), 'ends_at' => null]);
    SiteCache::watchNotice(); // remembers "hidden"
    $this->get('/tutorials')->assertDontSee('Maintenance tonight');

    $this->travel(10)->minutes();
    SiteCache::watchNotice(); // now visible: flush
    $this->get('/tutorials')->assertSee('Maintenance tonight');
});

it('sends security headers, with a CSP on public pages only', function () {
    $public = $this->get('/tutorials');
    $public->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    expect($public->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")->toContain('https://challenges.cloudflare.com')
        ->toContain('https://www.youtube-nocookie.com')->toContain("object-src 'none'");

    config(['ctp.csp.extra_hosts' => 'https://www.googletagmanager.com']);
    expect($this->get('/blog')->headers->get('Content-Security-Policy'))->toContain('https://www.googletagmanager.com');

    $admin = $this->get('/admin/login');
    $admin->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    expect($admin->headers->has('Content-Security-Policy'))->toBeFalse();
});

it('refuses to verify a backup into the live database', function () {
    config(['ctp.backup.verify_database' => config('database.connections.mysql.database')]);
    $this->artisan('ctp:backup-verify')->expectsOutputToContain('BACKUP_VERIFY_DATABASE')->assertFailed();
});

it('shows what needs attention on the dashboard', function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
    Comment::factory()->for($this->article)->create(['status' => 'pending']);
    ContactMessage::create(['topic' => 'general', 'name' => 'Aina', 'email' => 'a@example.com', 'message' => 'A question for you.']);
    $old = Article::factory()->published(now()->subYears(3))->create(['title' => 'Old CakePHP 3 Guide']);

    Livewire::test(SiteOverview::class)->assertOk()->assertSee(['Comments waiting', 'Unread messages', 'Missing pages']);
    Livewire::test(NeedsReview::class)->assertOk()->assertSee('Old CakePHP 3 Guide');
    Livewire::test(RecentActivity::class)->assertOk()->assertSee('Aina');
    Livewire::test(PopularArticles::class)->assertOk()->assertSee('Views (7 days)');
});

it('generates https URLs in production, whatever scheme the request came in on', function () {
    config(['app.url' => 'https://codethepixel.com']);
    app()->detectEnvironment(fn () => 'production');
    (new AppServiceProvider(app()))->boot();

    expect(url('/tutorials'))->toStartWith('https://')
        ->and(asset('favicon.svg'))->toStartWith('https://');

    URL::forceScheme('http'); // do not leak the forced scheme into other tests
});
