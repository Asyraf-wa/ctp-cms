<?php

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Article;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\User;
use Database\Factories\ArticleFactory;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
});

function validPage(array $overrides = []): array
{
    return [
        'title' => 'Privacy Policy',
        'slug' => 'privacy',
        'template' => 'toc',
        'is_published' => true,
        'body' => ArticleFactory::doc([['heading', 'What we collect'], ['paragraph', 'Only what you send through the contact form.']]),
        ...$overrides,
    ];
}

it('lists pages', function () {
    Page::factory()->create(['title' => 'About']);

    Livewire::test(ListPages::class)->assertOk()->assertSee('About');
});

it('creates a page, renders its body and serves it at domain/{slug}', function () {
    Livewire::test(CreatePage::class)->fillForm(validPage())->call('create')->assertHasNoFormErrors();

    $page = Page::sole();
    expect($page->body_rendered)->toContain('id="what-we-collect"')
        ->and($page->toc[0]['label'])->toBe('What we collect');

    $this->get('/privacy')->assertOk()->assertSee('Only what you send through the contact form.');
});

it('rejects slugs used by a tutorial or reserved for a section', function () {
    Article::factory()->published()->create(['slug' => 'cakephp-bake']);

    Livewire::test(CreatePage::class)->fillForm(validPage(['slug' => 'cakephp-bake']))->call('create')->assertHasFormErrors(['slug']);
    Livewire::test(CreatePage::class)->fillForm(validPage(['slug' => 'projects']))->call('create')->assertHasFormErrors(['slug']);
    Livewire::test(CreatePage::class)->fillForm(validPage(['slug' => 'Not A Slug']))->call('create')->assertHasFormErrors(['slug']);
});

it('keeps its own slug valid on edit and redirects a renamed slug', function () {
    $page = Page::factory()->create(validPage());

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertFormSet(['slug' => 'privacy'])
        ->fillForm(['slug' => 'privacy-policy'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Redirect::where('from_path', '/privacy')->value('to_path'))->toBe('/privacy-policy');
    $this->get('/privacy')->assertRedirect('/privacy-policy');
});

it('hides unpublished pages but lets the signed preview through', function () {
    $page = Page::factory()->create(validPage(['is_published' => false]));

    $this->get('/privacy')->assertNotFound();
    $this->get(URL::temporarySignedRoute('pages.preview', now()->addHour(), ['page' => $page]))
        ->assertOk()->assertSee('Preview')->assertSee('noindex, nofollow', false);
    $this->get(route('pages.preview', ['page' => $page]))->assertForbidden();
});

it('saves SEO overrides and tracks media used in the body', function () {
    $media = Media::factory()->create();

    Livewire::test(CreatePage::class)
        ->fillForm(validPage([
            'body' => ArticleFactory::doc([['image', $media->id]]),
            'seo' => ['title' => 'Privacy at CTP', 'description' => 'How CTP handles your data.'],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::sole();
    expect($page->seo->title)->toBe('Privacy at CTP')
        ->and(MediaUsage::where('usable_type', 'page')->where('usable_id', $page->id)->pluck('media_id')->all())->toBe([$media->id]);

    $this->get('/privacy')->assertSee('<title>Privacy at CTP', false)->assertSee('How CTP handles your data.');
});
