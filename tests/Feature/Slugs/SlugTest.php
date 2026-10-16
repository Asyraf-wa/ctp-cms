<?php

use App\Models\Page;
use App\Models\Redirect;
use App\Models\RootSlug;
use App\Models\User;
use App\Rules\AvailableRootSlug;
use App\Support\Slugs\RootSlugs;

it('generates a lowercase kebab-case slug from the title', function () {
    $page = Page::factory()->create(['title' => 'Privacy & Cookies Policy!']);

    expect($page->slug)->toBe('privacy-cookies-policy');
});

it('normalises a hand-written slug', function () {
    expect(Page::factory()->create(['slug' => 'My  About_Page '])->slug)->toBe('my-about-page');
});

it('keeps slugs unique by adding a suffix', function () {
    Page::factory()->create(['title' => 'About']);

    expect(Page::factory()->create(['title' => 'About'])->slug)->toBe('about-2');
});

it('never gives a root page a reserved slug', function (string $title) {
    expect(Page::factory()->create(['title' => $title])->slug)->toBe(strtolower($title).'-2');
})->with(['Admin', 'Blog', 'Tutorials', 'Contact', 'Build']);

it('registers the page in the shared root namespace', function () {
    $page = Page::factory()->create(['title' => 'About']);

    expect(RootSlug::find('about')->sluggable->is($page))->toBeTrue();

    $page->update(['slug' => 'about-me']);
    expect(RootSlug::find('about'))->toBeNull()
        ->and(RootSlug::find('about-me'))->not->toBeNull();

    $page->delete();
    expect(RootSlug::count())->toBe(0);
});

it('serves a published page at domain/{slug}', function () {
    Page::factory()->create(['title' => 'About']);
    Page::factory()->draft()->create(['title' => 'Secret']);

    $this->get('/about')->assertOk()->assertSee('About');
    $this->get('/secret')->assertNotFound();
    $this->get('/nothing-here')->assertNotFound();
});

it('redirects the old URL when a published slug changes', function () {
    $page = Page::factory()->create(['title' => 'About']);
    $page->update(['slug' => 'about-me']);

    $this->get('/about')->assertRedirect('/about-me')->assertStatus(301);
    expect(Redirect::sole()->source)->toBe(Redirect::SOURCE_SLUG);
});

it('does not create redirects for drafts', function () {
    Page::factory()->draft()->create(['title' => 'Draft'])->update(['slug' => 'draft-2']);

    expect(Redirect::count())->toBe(0);
});

it('flattens chains and removes loops when a slug changes repeatedly', function () {
    $page = Page::factory()->create(['title' => 'One']);
    $page->update(['slug' => 'two']);
    $page->update(['slug' => 'three']);

    expect(Redirect::pluck('to_path', 'from_path')->sortKeys()->all())->toBe(['/one' => '/three', '/two' => '/three']);

    $page->update(['slug' => 'one']); // back to the original

    $this->get('/one')->assertOk();
    expect(Redirect::pluck('to_path', 'from_path')->sortKeys()->all())->toBe(['/three' => '/one', '/two' => '/one']);
});

it('matches redirects case-insensitively, like the legacy mixed-case URLs', function () {
    Redirect::record('/codethepixel/articles/CakePHP-4-CRUD', '/cakephp-4-crud', Redirect::SOURCE_LEGACY);

    $this->get('/codethepixel/articles/CakePHP-4-CRUD')->assertRedirect('/cakephp-4-crud');
    expect(Redirect::sole()->hits)->toBe(1);
});

it('answers 410 Gone for removed content', function () {
    Redirect::record('/first-blog-post', null, Redirect::SOURCE_LEGACY);

    $this->get('/first-blog-post')->assertStatus(410);
});

it('never lets a page shadow a real route', function () {
    RootSlug::create(['slug' => 'admin', 'sluggable_type' => 'page', 'sluggable_id' => 999]); // forced past validation

    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('reserves every first route segment automatically', function () {
    expect(RootSlugs::reserved())->toContain('admin', 'auth', 'livewire', 'up', 'build', 'tutorials');
});

it('validates slugs typed in the admin', function () {
    $page = Page::factory()->create(['title' => 'About']);
    $validate = fn (string $slug, $ignore = null) => validator(['slug' => $slug], ['slug' => [new AvailableRootSlug($ignore)]])->passes();

    expect($validate('admin'))->toBeFalse()
        ->and($validate('about'))->toBeFalse()
        ->and($validate('about', $page))->toBeTrue()
        ->and($validate('new-page'))->toBeTrue();
});

it('reports slug conflicts before a new section is added', function () {
    Page::factory()->create(['title' => 'Courses Intro', 'slug' => 'workshops']);

    $this->artisan('ctp:check-slug-conflicts')->assertSuccessful();
    $this->artisan('ctp:check-slug-conflicts', ['--with' => ['workshops']])->assertFailed();
});

it('gives authors their own slug and redirects the old author URL', function () {
    $user = User::factory()->create(['name' => 'Asyraf Wahi Anuar']);
    expect($user->slug)->toBe('asyraf-wahi-anuar');

    $user->update(['slug' => 'asyraf']);
    expect(Redirect::sole()->only('from_path', 'to_path'))->toBe(['from_path' => '/author/asyraf-wahi-anuar', 'to_path' => '/author/asyraf']);
});
