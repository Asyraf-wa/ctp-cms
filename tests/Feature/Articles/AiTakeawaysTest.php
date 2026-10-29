<?php

use App\Ai\AiException;
use App\Ai\ArticleText;
use App\Ai\DeepSeek;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Settings\IntegrationSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

const AI_PROSE = 'Laravel queues move slow work out of the request. Configure the database driver, create a job class with the handle method, '
    .'dispatch it from a controller, and run a worker with artisan queue:work. On shared hosting without a worker, drain the queue from the scheduler every minute.';

function aiAnswer(array|string $takeaways): array
{
    return ['choices' => [['message' => ['content' => is_array($takeaways) ? json_encode(['takeaways' => $takeaways]) : $takeaways]]]];
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    updateSettings(IntegrationSettings::class, ['deepseek_api_key' => 'sk-test', 'deepseek_model' => null]);
});

it('asks DeepSeek for takeaways with the key, the model and only the title and prose', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(aiAnswer(['Queues move slow work out of the request.', 'Run a worker with artisan queue:work.']))]);

    $points = app(DeepSeek::class)->keyTakeaways('Laravel queues', AI_PROSE);

    expect($points)->toBe(['Queues move slow work out of the request.', 'Run a worker with artisan queue:work.']);
    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $request->url() === DeepSeek::ENDPOINT
            && $request->hasHeader('Authorization', 'Bearer sk-test')
            && $body['model'] === 'deepseek-chat'
            && $body['response_format'] === ['type' => 'json_object']
            && str_contains($body['messages'][1]['content'], 'Title: Laravel queues')
            && str_contains($body['messages'][1]['content'], 'artisan queue:work');
    });
});

it('cleans the answer: trims, strips bullets and html, drops duplicates and caps the list and the length', function () {
    $many = ['- First point', '1. Second point', '<b>Third</b> point', 'First point', '', 'Fourth', 'Fifth', 'Sixth', 'Seventh', str_repeat('x', 400)];
    Http::fake(['api.deepseek.com/*' => Http::response(aiAnswer($many))]);

    $points = app(DeepSeek::class)->keyTakeaways('T', AI_PROSE);

    expect($points)->toHaveCount(DeepSeek::MAX_POINTS)
        ->and($points[0])->toBe('First point')->and($points[1])->toBe('Second point')->and($points[2])->toBe('Third point')
        ->and(collect($points)->every(fn (string $p) => mb_strlen($p) <= DeepSeek::MAX_POINT_LENGTH))->toBeTrue();
});

it('stays inside the cost limit and uses a custom model', function () {
    updateSettings(IntegrationSettings::class, ['deepseek_model' => 'deepseek-reasoner']);
    Http::fake(['api.deepseek.com/*' => Http::response(aiAnswer(['A point']))]);

    app(DeepSeek::class)->keyTakeaways('T', str_repeat('word ', 20000));

    Http::assertSent(fn (Request $r) => $r->data()['model'] === 'deepseek-reasoner'
        && mb_strlen($r->data()['messages'][1]['content']) < DeepSeek::MAX_CHARS + 200);
});

it('explains what went wrong in words the admin can act on', function (callable $arrange, string $message) {
    $arrange();

    expect(fn () => app(DeepSeek::class)->keyTakeaways('T', AI_PROSE))->toThrow(AiException::class, $message);
})->with([
    'no key' => [fn () => updateSettings(IntegrationSettings::class, ['deepseek_api_key' => null]), 'API key in Settings'],
    'wrong key' => [fn () => Http::fake(['*' => Http::response([], 401)]), 'rejected the API key'],
    'no balance' => [fn () => Http::fake(['*' => Http::response([], 402)]), 'no balance'],
    'rate limit' => [fn () => Http::fake(['*' => Http::response([], 429)]), 'rate limiting'],
    'server error' => [fn () => Http::fake(['*' => Http::response([], 503)]), 'having trouble'],
    'not json' => [fn () => Http::fake(['*' => Http::response(aiAnswer('Sure! Here you go'))]), 'unexpected format'],
    'empty list' => [fn () => Http::fake(['*' => Http::response(aiAnswer([]))]), 'did not return any'],
]);

it('does not call DeepSeek when there is too little text', function () {
    Http::fake();

    expect(fn () => app(DeepSeek::class)->keyTakeaways('T', 'Too short.'))->toThrow(AiException::class, 'a little more');
    Http::assertNothingSent();
});

it('reads the plain prose out of the editor content', function () {
    $doc = ['type' => 'doc', 'content' => [
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Why queues']]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Slow work leaves the request.']]],
    ]];

    expect(ArticleText::from($doc))->toContain('Why queues')->toContain('Slow work leaves the request.')->not->toContain('<')
        ->and(ArticleText::from('<p>Hello &amp; <b>welcome</b></p>'))->toBe('Hello & welcome')
        ->and(ArticleText::from(null))->toBe('');
});

it('fills the takeaways field from the button in the article editor', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(aiAnswer(['Queues keep requests fast.', 'Drain the queue from the scheduler on shared hosting.']))]);
    $article = Article::factory()->create(['category_id' => Category::factory(), 'author_id' => $this->user->id, 'tldr' => null, 'body' => [
        'type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => AI_PROSE]]]],
    ]]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->callAction(TestAction::make('generateTldr')->schemaComponent('tldr-section'))
        ->assertHasNoActionErrors()
        ->assertSet('data.tldr', fn (array $tldr) => array_column(array_values($tldr), 'point') === ['Queues keep requests fast.', 'Drain the queue from the scheduler on shared hosting.']);

    // Nothing is saved until the admin saves the form.
    expect($article->fresh()->tldr)->toBeNull();
});

it('keeps generated takeaways as a plain list once the article is saved', function () {
    Http::fake(['api.deepseek.com/*' => Http::response(aiAnswer(['Queues keep requests fast.', 'Drain the queue from the scheduler on shared hosting.']))]);
    $article = Article::factory()->create(['category_id' => Category::factory(), 'author_id' => $this->user->id, 'tldr' => null, 'body' => [
        'type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => AI_PROSE]]]],
    ]]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->callAction(TestAction::make('generateTldr')->schemaComponent('tldr-section'))
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->fresh()->tldr)->toBe(['Queues keep requests fast.', 'Drain the queue from the scheduler on shared hosting.']);
});
