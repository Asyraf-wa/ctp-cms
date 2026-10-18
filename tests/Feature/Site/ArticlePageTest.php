<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Series;
use App\Models\Tag;
use App\Settings\DisplaySettings;
use Database\Factories\ArticleFactory;

beforeEach(function () {
    $this->parent = Category::factory()->create(['name' => 'PHP Frameworks']);
    $this->cake = Category::factory()->create(['name' => 'CakePHP', 'parent_id' => $this->parent->id]);
});

it('shows breadcrumbs, the TOC panel and the mobile helpers on a tutorial', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Bake Basics', 'category_id' => $this->cake->id,
        'body' => ArticleFactory::doc([['heading', 'Install'], ['paragraph', 'Run it.']]),
    ]);

    $this->get('/'.$article->slug)->assertOk()
        ->assertSeeInOrder(['Tutorials', 'PHP Frameworks', 'CakePHP', 'Bake Basics'])
        ->assertSee('data-reading-progress', false)
        ->assertSee('aria-label="Table of contents (mobile)"', false)
        ->assertSee('Back to top')
        ->assertSee('Published');
});

it('shows the series navigator with the reader position', function () {
    $series = Series::factory()->create(['title' => 'CakePHP from Zero']);
    $make = fn (string $title, int $part) => Article::factory()->published()->create([
        'title' => $title, 'category_id' => $this->cake->id, 'series_id' => $series->id, 'series_position' => $part,
    ]);
    $make('Install CakePHP', 1);
    $second = $make('Bake CRUD', 2);
    $make('Add Login', 3);

    $this->get('/'.$second->slug)->assertSee('Part 2 of 3')->assertSee('CakePHP from Zero')
        ->assertSeeInOrder(['Install CakePHP', 'Bake CRUD', 'Add Login']);
});

it('picks related articles by shared tags first, then category, never itself or another type', function () {
    $tag = Tag::factory()->create(['name' => 'Auth']);
    $other = Category::factory()->create(['name' => 'Elsewhere']);
    $article = Article::factory()->published()->create(['title' => 'Main One', 'category_id' => $this->cake->id]);
    $article->tags()->attach($tag);

    $tagged = Article::factory()->published()->create(['title' => 'Shares A Tag', 'category_id' => $other->id]);
    $tagged->tags()->attach($tag);
    Article::factory()->published()->create(['title' => 'Same Category', 'category_id' => $this->cake->id]);
    Article::factory()->published()->create(['title' => 'Also Same Category', 'category_id' => $this->cake->id]);
    // Newest, but only used to top up a short list.
    Article::factory()->published(now())->create(['title' => 'Unrelated', 'category_id' => $other->id]);
    Article::factory()->published()->blog()->create(['title' => 'Blog With Tag'])->tags()->attach($tag);

    $html = $this->get('/'.$article->slug)->assertOk()->getContent();
    $related = substr($html, strpos($html, 'id="related-heading"'));

    expect($related)->toContain('Shares A Tag')->toContain('Same Category')
        ->not->toContain('Unrelated')->not->toContain('Blog With Tag')
        ->and(strpos($related, 'Shares A Tag'))->toBeLessThan(strpos($related, 'Same Category'));
});

it('renders blog posts in the tutorial layout with the Blog section open in the sidebar', function () {
    $post = Article::factory()->published()->blog()->create(['title' => 'Why I Moved To Laravel']);
    Article::factory()->published()->blog()->create(['title' => 'Another Journal Entry']);

    $this->get('/blog/'.$post->slug)->assertOk()
        ->assertSee('Why I Moved To Laravel')
        ->assertSee('More from the blog')->assertSee('Another Journal Entry')
        ->assertSee('aria-label="Categories"', false)->assertSee('View all 2 posts');
});

it('lists as many sidebar tutorials per category as the Layout setting says, plus the one being read', function () {
    $articles = collect(range(1, 8))->map(fn (int $i) => Article::factory()->published(now()->subDays($i))->create([
        'title' => "Cake Tutorial {$i}", 'category_id' => $this->cake->id,
    ]));
    updateSettings(DisplaySettings::class, ['sidebar_items_per_category' => 3]);

    $html = $this->get('/'.$articles->last()->slug)->assertOk()->getContent();
    $start = strpos($html, 'aria-label="Categories"');
    $sidebar = substr($html, $start, strpos($html, '</aside>', $start) - $start);

    // Newest three, then the oldest because it is the article being read.
    expect(substr_count($sidebar, 'Cake Tutorial '))->toBe(4)
        ->and($sidebar)->toContain('Cake Tutorial 1')->toContain('Cake Tutorial 3')->not->toContain('Cake Tutorial 4<')
        ->and($sidebar)->toContain('View all 8 tutorials');
});
