<?php

use App\Content\MarkdownExporter;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Technology;
use Database\Factories\ArticleFactory;

beforeEach(function () {
    $this->cake = Category::factory()->create(['name' => 'CakePHP']);
});

it('exports TipTap documents, blocks included, as Markdown', function () {
    $media = Media::factory()->create(['alt' => 'Login form']);
    $doc = ['type' => 'doc', 'content' => [
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Install']]],
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'Run '],
            ['type' => 'text', 'text' => 'composer', 'marks' => [['type' => 'code']]],
            ['type' => 'text', 'text' => ', see '],
            ['type' => 'text', 'text' => 'the docs', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://book.cakephp.org']], ['type' => 'bold']]],
            ['type' => 'text', 'text' => ' (2 * 3).'],
        ]],
        ['type' => 'bulletList', 'content' => [
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'One']]]]],
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Two']]]]],
        ]],
        ['type' => 'customBlock', 'attrs' => ['id' => 'code', 'config' => ['language' => 'php', 'filename' => 'src/Application.php', 'code' => "<?php\necho '```';"]]],
        ['type' => 'customBlock', 'attrs' => ['id' => 'terminal', 'config' => ['commands' => "composer install\nbin/cake server", 'output' => 'Ready']]],
        ['type' => 'customBlock', 'attrs' => ['id' => 'callout', 'config' => ['type' => 'warning', 'body' => 'Back up first.']]],
        ['type' => 'customBlock', 'attrs' => ['id' => 'steps', 'config' => ['steps' => [['title' => 'Bake', 'body' => 'Run bake.'], ['title' => 'Test']]]]],
        ['type' => 'customBlock', 'attrs' => ['id' => 'image', 'config' => ['media_id' => $media->id, 'caption' => 'The form']]],
        ['type' => 'table', 'content' => [
            ['type' => 'tableRow', 'content' => [['type' => 'tableHeader', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'A']]]]], ['type' => 'tableHeader', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'B']]]]]]],
            ['type' => 'tableRow', 'content' => [['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '1']]]]], ['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '2']]]]]]],
        ]],
    ]];

    $md = app(MarkdownExporter::class)->export($doc);

    expect($md)
        ->toContain("## Install\n\nRun `composer`, see [**the docs**](https://book.cakephp.org) (2 \\* 3).")
        ->toContain("- One\n- Two")
        ->toContain("`src/Application.php`\n\n````php\n<?php\necho '```';\n````") // fence longer than the code's backticks
        ->toContain("```bash\n\$ composer install\n\$ bin/cake server\nReady\n```")
        ->toContain('> **Warning:** Back up first.')
        ->toContain("1. **Bake**\n   Run bake.\n2. **Test**")
        ->toContain('![Login form]('.$media->url().")\n*The form*")
        ->toContain("| A | B |\n| --- | --- |\n| 1 | 2 |");
});

it('serves Markdown twins of tutorials, posts and pages with the facts first', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Bake Basics', 'excerpt' => 'CRUD in minutes.', 'category_id' => $this->cake->id,
        'tldr' => ['Bake writes CRUD for you'], 'faq' => [['question' => 'Is it safe?', 'answer' => 'Yes.']],
        'body' => ArticleFactory::doc([['heading', 'Install'], ['paragraph', 'Run it.']]),
    ]);
    $article->techStack()->create(['technology_id' => Technology::factory()->create(['name' => 'CakePHP'])->id, 'version' => '5.2', 'sort' => 0]);
    $post = Article::factory()->published()->blog()->create(['title' => 'Journal']);
    Page::factory()->create(['title' => 'About', 'body' => ArticleFactory::doc([['paragraph', 'Hello there.']])]);
    $draft = Article::factory()->create(['category_id' => $this->cake->id]);

    $this->get('/'.$article->slug.'.md')->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
        ->assertHeader('Link', '<'.url('/'.$article->slug).'>; rel="canonical"')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSeeInOrder(['# Bake Basics', '> CRUD in minutes.', '- Tested with: CakePHP 5.2', '## Key takeaways', '- Bake writes CRUD for you', '## Install', 'Run it.', '## FAQ', '### Is it safe?'], false);
    $this->get('/blog/'.$post->slug.'.md')->assertOk()->assertSee('# Journal');
    $this->get('/about.md')->assertOk()->assertSee("# About\n", false)->assertSee('Hello there.');
    $this->get('/'.$draft->slug.'.md')->assertNotFound();
    $this->get('/'.$post->slug.'.md')->assertNotFound(); // blog posts live under /blog
});

it('publishes llms.txt and llms-full.txt', function () {
    $article = Article::factory()->published()->create(['title' => 'Bake Basics', 'excerpt' => 'CRUD in minutes.', 'category_id' => $this->cake->id, 'body' => ArticleFactory::doc([['paragraph', 'Full text here.']])]);
    Article::factory()->create(['title' => 'Secret Draft', 'category_id' => $this->cake->id]);

    $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSeeInOrder(['# Code The Pixel', '> ', '## CakePHP tutorials', '- [Bake Basics]('.url('/'.$article->slug).'.md): CRUD in minutes.'], false)
        ->assertDontSee('Secret Draft');
    $this->get('/llms-full.txt')->assertOk()->assertSee('Full text here.')->assertDontSee('Secret Draft');

    // Cached, but refreshed when content changes.
    $article->update(['title' => 'Bake Basics Renamed']);
    $this->get('/llms.txt')->assertSee('Bake Basics Renamed');
});

it('offers Copy as Markdown and Ask AI links on tutorials', function () {
    $article = Article::factory()->published()->create(['title' => 'Bake Basics', 'category_id' => $this->cake->id,
        'body' => ArticleFactory::doc([['heading', 'Install'], ['paragraph', 'Run it.']])]);

    $this->get('/'.$article->slug)->assertOk()
        ->assertSee('Copy page as Markdown')
        ->assertSee('https://claude.ai/new?q='.rawurlencode('Read '.url('/'.$article->slug).'.md'), false)
        ->assertSee('https://chatgpt.com/?hints=search&amp;q=', false);
});
