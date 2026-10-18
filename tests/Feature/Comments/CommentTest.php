<?php

use App\Filament\Resources\Comments\Pages\ListComments;
use App\Mail\CommentReply;
use App\Mail\NewComment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    updateSettings(GeneralSettings::class, ['notification_email' => 'owner@example.com']);
    $this->article = Article::factory()->published()->create(['category_id' => Category::factory()->create()->id]);
    $this->headers = ['Origin' => 'http://localhost', 'User-Agent' => 'Mozilla/5.0 Chrome/130'];
    config(['app.url' => 'http://localhost']);
});

function postComment(array $data = [], array $headers = [])
{
    return test()->withHeaders([...test()->headers, ...$headers])->postJson(route('api.articles.comments', test()->article), [
        'name' => 'Aina', 'email' => 'Aina@Example.com', 'body' => 'Does this work with `php-cs-fixer`?', '_elapsed' => 5000, ...$data,
    ]);
}

it('holds a first comment for moderation, renders it safely and emails the owner', function () {
    postComment(['body' => 'Try `composer` <script>alert(1)</script>', 'notify_replies' => true])
        ->assertOk()->assertJson(['status' => 'pending']);

    $comment = Comment::sole();
    expect($comment->status)->toBe('pending')
        ->and($comment->email)->toBe('aina@example.com')
        ->and($comment->body_html)->toContain('<code>composer</code>')->not->toContain('<script>')
        ->and($comment->unsubscribe_token)->toHaveLength(40)
        ->and($comment->visitor_hash)->toHaveLength(40);
    Mail::assertQueued(NewComment::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->hasReplyTo('aina@example.com'));

    // Not on the page until approved.
    $this->get('/'.$this->article->slug)->assertDontSee('php-cs-fixer');
});

it('approves comments from people already approved before', function () {
    Comment::factory()->for($this->article)->create(['email' => 'aina@example.com', 'status' => 'approved']);

    postComment()->assertOk()->assertJson(['status' => 'approved'])->assertJsonStructure(['html', 'id']);
    $this->get('/'.$this->article->slug)->assertSee('php-cs-fixer');
});

it('silently drops honeypot and too-fast submissions, and keeps link spam out of sight', function () {
    postComment(['website' => 'http://spam.example'])->assertOk()->assertJson(['status' => 'pending']);
    postComment(['_elapsed' => 400])->assertOk()->assertJson(['status' => 'pending']);
    expect(Comment::count())->toBe(0);

    postComment(['body' => 'Great https://a.example https://b.example https://c.example'])->assertOk();
    expect(Comment::sole())->status->toBe('spam')->spam_reason->toBe('too many links');
    Mail::assertNothingQueued();
});

it('requires Turnstile when it is configured', function () {
    updateSettings(IntegrationSettings::class, ['turnstile_site_key' => 'site', 'turnstile_secret_key' => 'secret']);
    Http::fake(['challenges.cloudflare.com/*' => fn ($request) => Http::response(['success' => $request['response'] === 'good'])]);

    postComment(['cf-turnstile-response' => 'bad'])->assertStatus(422);
    expect(Comment::count())->toBe(0);

    postComment(['cf-turnstile-response' => 'good'])->assertOk();
    expect(Comment::count())->toBe(1);
});

it('rejects other origins, closed articles, bad input and replies to replies', function () {
    postComment([], ['Origin' => 'https://evil.example'])->assertForbidden();
    postComment(['email' => 'nope', 'body' => ''])->assertStatus(422)->assertJsonValidationErrors(['email', 'body']);

    $top = Comment::factory()->for($this->article)->create(['status' => 'approved']);
    $child = Comment::factory()->for($this->article)->create(['status' => 'approved', 'parent_id' => $top->id]);
    $pending = Comment::factory()->for($this->article)->create(['status' => 'pending']);
    postComment(['parent_id' => $child->id])->assertJsonValidationErrors('parent_id');
    postComment(['parent_id' => $pending->id])->assertJsonValidationErrors('parent_id');
    postComment(['parent_id' => $top->id])->assertOk();

    $this->article->update(['comments_enabled' => false]);
    postComment()->assertForbidden();
    $this->get('/'.$this->article->slug)->assertSee('Comments are closed');

    $this->article->update(['comments_enabled' => true]);
    updateSettings(IntegrationSettings::class, ['comments_auto_close_days' => 1]);
    $this->article->update(['published_at' => now()->subDays(3)]);
    postComment()->assertForbidden();
});

it('lets the owner approve and reply as the author, emailing the commenter who asked', function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $owner = User::factory()->create(['email' => 'owner@example.com', 'name' => 'Asyraf']);
    $this->actingAs($owner);
    $comment = Comment::factory()->for($this->article)->create(['status' => 'pending', 'email' => 'aina@example.com', 'notify_replies' => true]);

    Livewire::test(ListComments::class)
        ->assertCanSeeTableRecords([$comment])
        ->callTableAction('reply', $comment, ['body' => 'Yes, use **php-cs-fixer**.'])
        ->assertHasNoTableActionErrors();

    $reply = Comment::where('user_id', $owner->id)->sole();
    expect($comment->fresh()->status)->toBe('approved')
        ->and($reply->parent_id)->toBe($comment->id)
        ->and($reply->status)->toBe('approved');
    Mail::assertQueued(CommentReply::class, fn ($mail) => $mail->hasTo('aina@example.com'));

    $this->get('/'.$this->article->slug)->assertSee('Author')->assertSee('<strong>php-cs-fixer</strong>', false);

    // One click stops reply emails for that address.
    $this->get(route('comments.unsubscribe', $comment->fresh()->unsubscribe_token))->assertOk()->assertSee('No more reply emails');
    expect($comment->fresh()->notify_replies)->toBeFalse();
});

it('rate limits comments per IP', function () {
    foreach (range(1, 10) as $i) {
        postComment(['body' => "Comment number {$i}"]);
    }
    postComment()->assertStatus(429);
});
