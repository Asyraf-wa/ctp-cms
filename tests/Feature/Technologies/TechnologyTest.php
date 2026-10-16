<?php

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Technologies\Pages\ManageTechnologies;
use App\Models\Article;
use App\Models\Category;
use App\Models\Technology;
use App\Models\User;
use App\Support\Contrast;
use App\Support\SimpleIcons;
use Database\Factories\ArticleFactory;
use Livewire\Livewire;

beforeEach(function () {
    $this->cake = Category::factory()->create(['name' => 'CakePHP']);
});

function actAsOwner(): User
{
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $user = User::factory()->create(['email' => 'owner@example.com']);
    test()->actingAs($user);

    return $user;
}

it('finds Simple Icons by name, exact matches first, and matches brand names', function () {
    expect(array_key_first(SimpleIcons::search('laravel')))->toBe('laravel')
        ->and(SimpleIcons::find('cakephp'))->toBe(['slug' => 'cakephp', 'title' => 'CakePHP', 'hex' => '#D33C43'])
        ->and(SimpleIcons::match('Joomla!'))->toBe('joomla')
        ->and(SimpleIcons::match('Vue.js'))->toBe('vuedotjs')
        ->and(SimpleIcons::match('Tools & Software'))->toBeNull();
});

it('renders the real brand SVG, takes the brand colour and keeps it readable in dark mode', function () {
    $laravel = Technology::factory()->brand('laravel', 'Laravel')->create();
    $github = Technology::factory()->brand('github', 'GitHub')->create();

    // The Laravel path, not the generic fallback icon.
    expect((string) $laravel->iconHtml())->toContain('fill="currentColor"')
        ->and((string) $laravel->iconHtml())->toBe(svg('si-laravel', 'size-4', ['fill' => 'currentColor', 'aria-hidden' => 'true'])->toHtml())
        ->and($laravel->color)->toBe('#FF2D20')
        ->and($github->color)->toBe('#181717')
        ->and($github->darkColor())->not->toBe('#181717')
        ->and(Contrast::ratio(Contrast::luminance($github->darkColor()), Contrast::luminance('#111a2e')))->toBeGreaterThanOrEqual(3.0);
});

it('falls back to a generic icon when a brand logo is missing', function () {
    $gone = Technology::factory()->create(['icon_type' => 'simple', 'icon' => 'not-a-real-brand']);

    expect((string) $gone->iconHtml())->toBe(svg('lucide-code-xml', 'size-4', ['aria-hidden' => 'true'])->toHtml());
});

it('manages technologies in the admin and fills the colour from the brand', function () {
    actAsOwner();

    Livewire::test(ManageTechnologies::class)
        ->callAction('create', ['icon_type' => 'simple', 'icon' => 'php', 'name' => 'PHP', 'slug' => 'php'])
        ->assertHasNoActionErrors();

    expect(Technology::sole())->name->toBe('PHP')->color->toBe('#777BB4');
});

it('saves an ordered tech stack with versions; the first is the main technology', function () {
    actAsOwner();
    $php = Technology::factory()->brand('php', 'PHP')->create();
    $laravel = Technology::factory()->brand('laravel', 'Laravel')->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Queues on Shared Hosting', 'slug' => 'queues-on-shared-hosting', 'type' => 'tutorial',
            'author_id' => auth()->id(), 'category_id' => $this->cake->id, 'status' => 'draft',
            'body' => ArticleFactory::doc([['paragraph', 'Use cron.']]),
            'techStack' => [['technology_id' => $laravel->id, 'version' => '13.x'], ['technology_id' => $php->id, 'version' => '8.4']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::sole();
    expect($article->technologies->map(fn ($t) => $t->name.' '.$t->pivot->version)->all())->toBe(['Laravel 13.x', 'PHP 8.4'])
        ->and($article->primaryTechnology()->is($laravel))->toBeTrue();
});

it('shows the main logo in lists and the whole stack on the tutorial page', function () {
    $laravel = Technology::factory()->brand('laravel', 'Laravel')->create();
    $php = Technology::factory()->brand('php', 'PHP')->create();
    $article = Article::factory()->published()->create(['title' => 'Laravel Queues', 'category_id' => $this->cake->id]);
    $article->techStack()->createMany([['technology_id' => $laravel->id, 'version' => '13.x', 'sort' => 0], ['technology_id' => $php->id, 'sort' => 1]]);

    $this->get('/preview/home')->assertOk()->assertSee('--tech: #FF2D20', false);

    $html = $this->get('/'.$article->slug)->assertOk()->assertSee('Tech stack')->getContent();
    $start = strpos($html, 'id="tech-stack-heading"');
    $panel = substr($html, $start, strpos($html, '</section>', $start) - $start);
    expect($panel)->toContain('Laravel')->toContain('13.x')->toContain('PHP');
});

it('uses the category technology for tutorials without a stack and in the sidebar', function () {
    $cake = Technology::factory()->brand('cakephp', 'CakePHP')->create();
    $this->cake->update(['technology_id' => $cake->id]);
    $article = Article::factory()->published()->create(['title' => 'Bake Basics', 'category_id' => $this->cake->id]);

    expect($article->fresh()->primaryTechnology()->is($cake))->toBeTrue();
    $this->get('/preview/home')->assertSee('--tech: #D33C43', false);
    $html = $this->get('/'.$article->slug)->getContent();
    $start = strpos($html, 'aria-label="Categories"');
    expect(substr($html, $start, strpos($html, '</aside>', $start) - $start))->toContain('--tech: #D33C43');
});
