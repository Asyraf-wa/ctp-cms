<?php

use App\Filament\Widgets\PopularArticles;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

$browser = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) Chrome/130.0', 'Origin' => 'http://localhost'];

beforeEach(function () {
    config(['app.url' => 'http://localhost']);
    $this->article = Article::factory()->published()->create(['views' => 10, 'kudos' => 5]);
});

it('counts one view per visitor per day and never touches updated_at', function () use ($browser) {
    $updated = $this->article->updated_at;
    $url = route('api.articles.view', $this->article);

    $this->withHeaders($browser)->post($url)->assertNoContent();
    $this->withHeaders($browser)->post($url)->assertNoContent();
    $this->withHeaders($browser)->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])->post($url)->assertNoContent();

    $fresh = $this->article->fresh();
    expect($fresh->views)->toBe(12)->and($fresh->updated_at->equalTo($updated))->toBeTrue();
});

it('ignores bots, other origins and unpublished articles', function () use ($browser) {
    $url = route('api.articles.view', $this->article);
    $this->withHeaders([...$browser, 'User-Agent' => 'Googlebot/2.1'])->post($url)->assertNoContent();
    $this->withHeaders([...$browser, 'Origin' => 'https://evil.example'])->post($url)->assertNoContent();
    $draft = Article::factory()->create();
    $this->withHeaders($browser)->post(route('api.articles.view', $draft))->assertNoContent();

    expect($this->article->fresh()->views)->toBe(10)->and(DB::table('article_views')->count())->toBe(0);
});

it('accepts one kudos per visitor', function () use ($browser) {
    $url = route('api.articles.kudos', $this->article);

    $this->withHeaders($browser)->postJson($url)->assertOk()->assertJson(['count' => 6, 'given' => true]);
    $this->withHeaders($browser)->postJson($url)->assertOk()->assertJson(['count' => 6]);
    $this->withHeaders([...$browser, 'Origin' => 'https://evil.example'])->postJson($url)->assertForbidden();
});

it('shows the kudos button and the beacon on public articles but not in preview', function () {
    $this->get('/'.$this->article->slug)->assertSee('This helped me')->assertSee('data-view-beacon', false);
});

it('rolls finished days into daily totals and prunes raw rows', function () {
    DB::table('article_views')->insert([
        ['article_id' => $this->article->id, 'viewed_on' => now()->subDay()->toDateString(), 'visitor_hash' => str_repeat('a', 40)],
        ['article_id' => $this->article->id, 'viewed_on' => now()->subDay()->toDateString(), 'visitor_hash' => str_repeat('b', 40)],
        ['article_id' => $this->article->id, 'viewed_on' => now()->toDateString(), 'visitor_hash' => str_repeat('c', 40)],
    ]);

    $this->artisan('ctp:rollup-views')->assertSuccessful();

    expect(DB::table('article_view_stats')->where('article_id', $this->article->id)->value('views'))->toBe(2)
        ->and(DB::table('article_views')->count())->toBe(1); // today's row stays for deduplication
});

it('lists the most helpful articles on the dashboard', function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
    Article::factory()->published()->create(['title' => 'Very Helpful', 'kudos' => 500]);

    Livewire::test(PopularArticles::class)->assertOk()->assertSeeInOrder(['Very Helpful', $this->article->title]);
});
