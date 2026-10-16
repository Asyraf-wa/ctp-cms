<?php

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Series\Pages\ManageSeries;
use App\Filament\Resources\Tags\Pages\ManageTags;
use App\Models\Category;
use App\Models\Redirect;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
});

function tagArticles(Tag $tag, array $articleIds): void
{
    DB::table('article_tag')->insert(array_map(fn ($id) => ['article_id' => $id, 'tag_id' => $tag->id], $articleIds));
}

it('lists categories as a two-level tree for selects', function () {
    $php = Category::factory()->create(['name' => 'PHP', 'sort' => 1]);
    Category::factory()->create(['name' => 'Laravel', 'parent_id' => $php->id]);
    Category::factory()->create(['name' => 'Tools', 'sort' => 2]);

    expect(array_values(Category::treeOptions()))->toBe(['PHP', '— Laravel', 'Tools']);
});

it('creates a category with a slug from its name', function () {
    Livewire::test(ManageCategories::class)
        ->callAction('create', ['name' => 'Tools & Software', 'slug' => 'tools-software', 'icon' => 'wrench'])
        ->assertHasNoActionErrors();

    expect(Category::sole()->only('name', 'slug', 'icon'))->toBe(['name' => 'Tools & Software', 'slug' => 'tools-software', 'icon' => 'wrench']);
});

it('keeps the category tree at two levels', function () {
    $parent = Category::factory()->create(['name' => 'PHP']);
    Category::factory()->create(['name' => 'Laravel', 'parent_id' => $parent->id]);
    $other = Category::factory()->create(['name' => 'Tools']);

    Livewire::test(ManageCategories::class)
        ->callAction(TestAction::make('edit')->table($parent), ['parent_id' => $other->id])
        ->assertHasActionErrors(['parent_id']);
});

it('rejects an invalid slug', function () {
    Livewire::test(ManageTags::class)
        ->callAction('create', ['name' => 'API', 'slug' => 'Bad Slug'])
        ->assertHasActionErrors(['slug']);
});

it('redirects the old category URL after a slug change', function () {
    Category::factory()->create(['name' => 'Joomla!'])->update(['slug' => 'joomla-cms']);

    expect(Redirect::sole()->only('from_path', 'to_path'))->toBe(['from_path' => '/category/joomla', 'to_path' => '/category/joomla-cms']);
});

it('merges a tag into another without duplicate links', function () {
    $cake4 = Tag::factory()->create(['name' => 'CakePHP4']);
    $cake = Tag::factory()->create(['name' => 'CakePHP']);
    tagArticles($cake4, [1, 2, 3]);
    tagArticles($cake, [3, 4]);

    $cake4->mergeInto($cake);

    expect(Tag::find($cake4->id))->toBeNull()
        ->and(DB::table('article_tag')->where('tag_id', $cake->id)->orderBy('article_id')->pluck('article_id')->all())->toBe([1, 2, 3, 4])
        ->and(Redirect::sole()->only('from_path', 'to_path'))->toBe(['from_path' => '/tag/cakephp4', 'to_path' => '/tag/cakephp']);
});

it('merges tags from the admin, one or many at a time', function () {
    $target = Tag::factory()->create(['name' => 'Auth']);
    $a = Tag::factory()->create(['name' => 'Authentication']);
    $b = Tag::factory()->create(['name' => 'Login']);
    $c = Tag::factory()->create(['name' => 'Logout']);
    tagArticles($a, [1]);

    Livewire::test(ManageTags::class)
        ->assertTableColumnStateSet('articles_count', 1, $a)
        ->callAction(TestAction::make('merge')->table($a), ['target_id' => $target->id])
        ->assertHasNoActionErrors()
        ->callTableBulkAction('mergeSelected', [$b, $c, $target], ['target_id' => $target->id]);

    expect(Tag::pluck('name')->all())->toBe(['Auth'])
        ->and(DB::table('article_tag')->where('tag_id', $target->id)->count())->toBe(1);
});

it('manages series', function () {
    Livewire::test(ManageSeries::class)
        ->callAction('create', ['title' => 'CakePHP 5 from Zero', 'slug' => 'cakephp-5-from-zero'])
        ->assertHasNoActionErrors();

    expect(Series::sole()->publicPath())->toBe('/series/cakephp-5-from-zero');
});
