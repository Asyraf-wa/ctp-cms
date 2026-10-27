<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Support\Slugs\RootSlugs;

// A page for people (the XML sitemap is for crawlers): every public section and tutorial on one screen.
it('lists published tutorials by category, blog posts, series and pages, and leaves drafts out', function () {
    $category = Category::factory()->create(['name' => 'Joomla']);
    Article::factory()->published()->create(['title' => 'Joomla Upgrade Guide', 'category_id' => $category->id]);
    Article::factory()->create(['title' => 'Secret Draft Tutorial', 'category_id' => $category->id, 'status' => ArticleStatus::Draft]);
    Article::factory()->published()->blog()->create(['title' => 'My Blog Opinion']);
    Page::query()->create(['title' => 'About Me', 'slug' => 'about-me', 'is_published' => true, 'body' => ['type' => 'doc', 'content' => []], 'sort' => 1]);

    $this->get('/sitemap')->assertOk()
        ->assertSee('Joomla Upgrade Guide')->assertSee('Joomla')->assertSee('My Blog Opinion')->assertSee('About Me')
        ->assertSee('/sitemap.xml', false)->assertSee('/feed', false)
        ->assertDontSee('Secret Draft Tutorial');
});

it('works with no content at all', function () {
    $this->get('/sitemap')->assertOk()->assertSee('Main sections')->assertDontSee('Tutorials by category');
});

it('is not mistaken for an article slug', function () {
    // "sitemap" is a route prefix, so it is reserved in the shared slug namespace.
    expect(RootSlugs::isReserved('sitemap'))->toBeTrue();
});
