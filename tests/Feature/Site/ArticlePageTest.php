<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Series;
use App\Models\Tag;
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

it('renders blog posts in a single column with more posts', function () {
    $post = Article::factory()->published()->blog()->create(['title' => 'Why I Moved To Laravel']);
    Article::factory()->published()->blog()->create(['title' => 'Another Journal Entry']);

    $this->get('/blog/'.$post->slug)->assertOk()
        ->assertSee('Why I Moved To Laravel')
        ->assertSee('More from the blog')->assertSee('Another Journal Entry')
        ->assertDontSee('aria-label="Categories"', false);
});
