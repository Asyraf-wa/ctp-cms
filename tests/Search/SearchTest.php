<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Search\Search;

beforeEach(function () {
    $this->cake = Category::factory()->create(['name' => 'CakePHP']);
});

it('finds published articles by title, excerpt and body with prefix matching', function () {
    Article::factory()->published()->create(['title' => 'CakePHP Authentication Plugin', 'category_id' => $this->cake->id]);
    Article::factory()->published()->create(['title' => 'Bake Basics', 'excerpt' => 'Generate authentication scaffolding', 'category_id' => $this->cake->id]);
    Article::factory()->create(['title' => 'Draft Authentication Notes', 'category_id' => $this->cake->id]);

    $this->get('/search?q=authen')->assertOk()
        ->assertSee('2 results')
        ->assertSeeInOrder(['Plugin', 'Bake Basics']) // title matches rank first (terms are wrapped in <mark>)
        ->assertDontSee('Draft Authentication Notes')
        ->assertSee('noindex, follow', false);
});

it('highlights terms safely', function () {
    expect(Search::highlight('Auth <script>', 'auth'))->toBe('<mark>Auth</mark> &lt;script&gt;');
});

it('ignores boolean operators and handles short terms', function () {
    Article::factory()->published()->create(['title' => 'Dark Mode UI Tricks', 'category_id' => $this->cake->id]);
    Article::factory()->published()->create(['title' => 'Dark Mode Database', 'category_id' => $this->cake->id]);

    expect(Search::terms('+dark -mode* "ui"'))->toBe(['fulltext' => ['dark', 'mode'], 'short' => ['ui']]);
    $this->get('/search?q='.urlencode('dark ui'))->assertSee('1 result')->assertSee('Tricks')->assertDontSee('Mode Database');
});

it('returns grouped quick results for the palette', function () {
    $tag = Tag::factory()->create(['name' => 'CakePHP 5']);
    Article::factory()->published()->create(['title' => 'CakePHP Routing', 'category_id' => $this->cake->id])->tags()->attach($tag);
    Article::factory()->published()->blog()->create(['title' => 'Why CakePHP Still Matters']);

    $response = $this->getJson('/api/search?q=cakephp')->assertOk();

    expect(collect($response->json('groups'))->pluck('label')->all())->toBe(['Tutorials', 'Blog', 'Categories', 'Tags'])
        ->and($response->json('groups.0.items.0'))->toMatchArray(['title' => 'CakePHP Routing', 'meta' => 'CakePHP', 'url' => url('/cakephp-routing')]);

    $this->getJson('/api/search?q=c')->assertOk()->assertJson(['groups' => []]);
});

it('shows popular tutorials on an empty search and a friendly empty state', function () {
    Article::factory()->published()->create(['title' => 'Most Read Tutorial', 'category_id' => $this->cake->id, 'views' => 99]);

    $this->get('/search')->assertOk()->assertSee('Popular tutorials')->assertSee('Most Read Tutorial');
    $this->get('/search?q=nothingmatcheszz')->assertOk()->assertSee('No results');
});
