<?php

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Article;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\User;
use Database\Factories\ArticleFactory;
use Livewire\Livewire;

beforeEach(function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
});

function validProject(array $overrides = []): array
{
    return [
        'title' => 'CTP SaaS',
        'slug' => 'ctp-saas',
        'tagline' => 'Generate CakePHP and Laravel admin panels from a schema.',
        'status' => 'active',
        'is_published' => true,
        'stack' => ['Laravel', 'Vue', 'Tailwind CSS'],
        'live_url' => 'https://example.com',
        'repo_url' => 'https://github.com/example/ctp',
        'started_at' => '2024-03-01',
        'body' => ArticleFactory::doc([['heading', 'The problem'], ['paragraph', 'Admin panels take weeks.']]),
        ...$overrides,
    ];
}

it('lists projects', function () {
    Project::factory()->create(['title' => 'Pixel Maps']);

    Livewire::test(ListProjects::class)->assertOk()->assertSee('Pixel Maps');
});

it('creates a project with cover, gallery, stack and related tutorials in order', function () {
    [$cover, $shot1, $shot2] = Media::factory()->count(3)->create();
    $first = Article::factory()->published()->create(['title' => 'Part one']);
    $second = Article::factory()->published()->create(['title' => 'Part two']);

    Livewire::test(CreateProject::class)
        ->fillForm(validProject([
            'cover_media_id' => $cover->id,
            'gallery' => [['media_id' => $shot1->id, 'caption' => 'Dashboard'], ['media_id' => $shot2->id, 'caption' => null]],
            'articles' => [$second->id, $first->id],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::sole();
    expect($project->status)->toBe(ProjectStatus::Active)
        ->and($project->stack)->toBe(['Laravel', 'Vue', 'Tailwind CSS'])
        ->and($project->publicPath())->toBe('/projects/ctp-saas')
        ->and($project->body_rendered)->toContain('id="the-problem"')
        ->and($project->period())->toBe('2024 – present')
        ->and($project->articles->pluck('title')->all())->toBe(['Part two', 'Part one'])
        ->and(collect($project->gallery)->pluck('caption')->all())->toBe(['Dashboard', null]);

    $usage = MediaUsage::where('usable_type', 'project')->where('usable_id', $project->id)->pluck('media_id')->sort()->values()->all();
    expect($usage)->toBe(collect([$cover->id, $shot1->id, $shot2->id])->sort()->values()->all());
});

it('validates links and the timeline', function () {
    Livewire::test(CreateProject::class)
        ->fillForm(validProject(['live_url' => 'not a url', 'started_at' => '2025-01-01', 'ended_at' => '2024-01-01']))
        ->call('create')
        ->assertHasFormErrors(['live_url', 'ended_at']);
});

it('redirects the old URL when the slug changes', function () {
    $project = Project::factory()->create(['title' => 'Pixel Maps']);

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm(['slug' => 'interactive-svg-maps'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Redirect::where('from_path', '/projects/pixel-maps')->value('to_path'))->toBe('/projects/interactive-svg-maps');
});

it('tidies the stack and offers it for filtering', function () {
    Project::factory()->create(['stack' => [' Laravel ', 'Alpine.js', 'Laravel', '']]);
    Project::factory()->create(['stack' => ['CakePHP']]);
    Project::factory()->draft()->create(['stack' => ['Secret']]);

    expect(Project::first()->stack)->toBe(['Laravel', 'Alpine.js'])
        ->and(Project::stackOptions())->toBe(['Alpine.js', 'CakePHP', 'Laravel']);
});

it('can be linked from a menu and drops out when unpublished', function () {
    $project = Project::factory()->create(['title' => 'Pixel Maps']);
    $menu = Menu::where('location', 'header')->sole();
    $item = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Maps', 'type' => 'project', 'linkable_id' => $project->id]);

    expect($item->fresh()->href())->toBe(url('/projects/pixel-maps'));

    $project->update(['is_published' => false]);
    expect($item->fresh()->isBroken())->toBeTrue();
});
