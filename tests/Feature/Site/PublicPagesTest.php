<?php

use App\Enums\Difficulty;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Project;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Support\Slugs\RootSlugs;

beforeEach(function () {
    $this->cake = Category::factory()->create(['name' => 'CakePHP']);
    $this->auth = Category::factory()->create(['name' => 'Auth', 'parent_id' => $this->cake->id]);
    $this->tools = Category::factory()->create(['name' => 'Tools']);
});

it('renders the home page with featured, latest and categories but no drafts', function () {
    Article::factory()->published()->create(['title' => 'Featured Bake', 'category_id' => $this->cake->id, 'is_featured' => true]);
    Article::factory()->published()->create(['title' => 'Latest Tools', 'category_id' => $this->tools->id]);
    Article::factory()->create(['title' => 'Secret Draft', 'category_id' => $this->cake->id]);
    Article::factory()->published()->blog()->create(['title' => 'Journal Entry']);
    Project::factory()->create(['title' => 'Pixel Maps']);

    $this->get('/')->assertOk()
        ->assertSeeInOrder(['Featured tutorials', 'Featured Bake', 'Latest tutorials', 'Latest Tools', 'Explore by category', 'From the blog', 'Journal Entry', 'Projects', 'Pixel Maps'])
        ->assertDontSee('Secret Draft');
});

it('filters tutorials by category (with children), level and tag, keeping filters in the URL', function () {
    $tag = Tag::factory()->create(['name' => 'Security']);
    $parent = Article::factory()->published()->create(['title' => 'Bake Basics', 'category_id' => $this->cake->id, 'difficulty' => Difficulty::Beginner]);
    $child = Article::factory()->published()->create(['title' => 'Login Plugin', 'category_id' => $this->auth->id, 'difficulty' => Difficulty::Advanced]);
    Article::factory()->published()->create(['title' => 'VS Code Setup', 'category_id' => $this->tools->id]);
    $child->tags()->attach($tag);

    $this->get('/tutorials')->assertOk()->assertSee(['Bake Basics', 'Login Plugin', 'VS Code Setup']);
    $this->get('/tutorials?category=cakephp')->assertSee(['Bake Basics', 'Login Plugin'])->assertDontSee('VS Code Setup')
        ->assertSee('noindex, follow', false);
    $this->get('/tutorials?category=cakephp&difficulty=advanced')->assertSee('Login Plugin')->assertDontSee('Bake Basics')
        ->assertSee('category=cakephp', false); // the other filters stay in the links
    $this->get('/tutorials?tag=security')->assertSee('Login Plugin')->assertDontSee('Bake Basics');
    $this->get('/tutorials?difficulty=nonsense&category=missing')->assertOk()->assertSee('VS Code Setup');
});

it('sorts tutorials by popularity', function () {
    Article::factory()->published(now()->subDays(1))->create(['title' => 'New But Quiet', 'category_id' => $this->tools->id, 'views' => 3]);
    Article::factory()->published(now()->subDays(9))->create(['title' => 'Old But Loved', 'category_id' => $this->tools->id, 'views' => 900]);

    $this->get('/tutorials')->assertSeeInOrder(['New But Quiet', 'Old But Loved']);
    $this->get('/tutorials?sort=popular')->assertSeeInOrder(['Old But Loved', 'New But Quiet']);
});

it('shows category archives including child categories, and redirects renamed ones', function () {
    Article::factory()->published()->create(['title' => 'Login Plugin', 'category_id' => $this->auth->id]);
    Article::factory()->published()->create(['title' => 'VS Code Setup', 'category_id' => $this->tools->id]);

    // The sidebar lists every category, so check the archive's own count.
    $this->get('/category/cakephp')->assertOk()->assertSee('Login Plugin')->assertSee('1 tutorial</p>', false);
    $this->get('/category/nope')->assertNotFound();

    $this->cake->update(['slug' => 'cake-php']);
    $this->get('/category/cakephp')->assertRedirect('/category/cake-php');
});

it('shows tag archives with tutorials and blog posts', function () {
    $tag = Tag::factory()->create(['name' => 'Docker']);
    Article::factory()->published()->create(['title' => 'Docker Tutorial', 'category_id' => $this->tools->id])->tags()->attach($tag);
    Article::factory()->published()->blog()->create(['title' => 'Docker Thoughts'])->tags()->attach($tag);
    Article::factory()->create(['title' => 'Docker Draft'])->tags()->attach($tag);

    $this->get('/tag/docker')->assertOk()->assertSee(['Docker Tutorial', 'Docker Thoughts'])->assertDontSee('Docker Draft');
});

it('lists a series in reading order and hides empty series', function () {
    $series = Series::factory()->create(['title' => 'CakePHP from Zero']);
    Article::factory()->published()->create(['title' => 'Second Step', 'category_id' => $this->cake->id, 'series_id' => $series->id, 'series_position' => 2]);
    Article::factory()->published()->create(['title' => 'First Step', 'category_id' => $this->cake->id, 'series_id' => $series->id, 'series_position' => 1]);
    $empty = Series::factory()->create(['title' => 'Coming Soon']);

    $this->get('/series')->assertOk()->assertSee('CakePHP from Zero')->assertDontSee('Coming Soon');
    $this->get('/series/'.$series->slug)->assertOk()->assertSeeInOrder(['First Step', 'Second Step']);
    $this->get('/series/'.$empty->slug)->assertNotFound();
});

it('lists only blog posts on the blog index', function () {
    Article::factory()->published()->blog()->create(['title' => 'Journal Entry']);
    Article::factory()->published()->create(['title' => 'A Tutorial', 'category_id' => $this->tools->id]);

    $this->get('/blog')->assertOk()->assertSee('Journal Entry')->assertDontSee('A Tutorial');
});

it('filters projects by tech and shows the detail page with gallery and related tutorials', function () {
    $shot = Media::factory()->create();
    $tutorial = Article::factory()->published()->create(['title' => 'Built Along The Way', 'category_id' => $this->tools->id]);
    $maps = Project::factory()->create(['title' => 'Pixel Maps', 'stack' => ['Alpine.js', 'SVG'], 'gallery' => [['media_id' => $shot->id, 'caption' => 'Map editor']]]);
    $maps->articles()->attach($tutorial, ['sort' => 0]);
    Project::factory()->create(['title' => 'CTP SaaS', 'stack' => ['Laravel']]);
    Project::factory()->draft()->create(['title' => 'Hidden Project']);

    $this->get('/projects')->assertOk()->assertSee(['Pixel Maps', 'CTP SaaS'])->assertDontSee('Hidden Project');
    $this->get('/projects?tech=SVG')->assertSee('Pixel Maps')->assertDontSee('CTP SaaS');
    $this->get('/projects/'.$maps->slug)->assertOk()->assertSee(['Map editor', 'Built Along The Way', 'At a glance']);
    $this->get('/projects/hidden-project')->assertNotFound();
});

it('shows the author page with their published articles', function () {
    $author = User::factory()->create(['name' => 'Asyraf Wahi Anuar', 'job_title' => 'Lecturer', 'bio_long' => '<p>Teaches <script>alert(1)</script>web dev.</p>']);
    Article::factory()->published()->create(['title' => 'By Asyraf', 'author_id' => $author->id, 'category_id' => $this->tools->id]);
    Article::factory()->create(['title' => 'Unpublished By Asyraf', 'author_id' => $author->id]);

    $this->get('/author/'.$author->slug)->assertOk()
        ->assertSee(['Asyraf Wahi Anuar', 'Lecturer', 'By Asyraf', 'Teaches'])
        ->assertDontSee('Unpublished By Asyraf')
        ->assertDontSee('<script>alert(1)</script>', false);
    $this->get('/author/nobody')->assertNotFound();
});

it('reserves the new section names for root slugs', function () {
    foreach (['tutorials', 'category', 'tag', 'series', 'blog', 'projects', 'author'] as $word) {
        expect(RootSlugs::isReserved($word))->toBeTrue();
    }
});

it('renders the text-only home sample with category tabs and discovery panels', function () {
    Article::factory()->published()->create(['title' => 'Login Plugin', 'category_id' => $this->auth->id, 'difficulty' => Difficulty::Advanced]);
    Article::factory()->published()->create(['title' => 'VS Code Setup', 'category_id' => $this->tools->id, 'views' => 50]);
    Article::factory()->create(['title' => 'Secret Draft', 'category_id' => $this->cake->id]);

    $this->get('/preview/home')->assertOk()
        ->assertSee('noindex, follow', false)
        ->assertSee(['Latest tutorials', 'Login Plugin', 'VS Code Setup', 'Level', 'Advanced', 'Popular this month'])
        ->assertDontSee('Secret Draft')
        ->assertDontSee('<img', false);

    // The CakePHP tab includes its child category.
    $html = $this->get('/preview/home?category=cakephp')->assertOk()->assertSee('Latest CakePHP tutorials')->getContent();
    expect(substr($html, strpos($html, '<ol>'), 2000))->toContain('Login Plugin')->not->toContain('VS Code Setup');
});
