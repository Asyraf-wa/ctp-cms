<?php

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Mail\ContactAutoReply;
use App\Mail\ContactReceived;
use App\Mail\ContactReplyMail;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\User;
use App\Settings\ContactSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config(['app.url' => 'http://localhost']);
    updateSettings(GeneralSettings::class, ['notification_email' => 'owner@example.com', 'contact_email' => 'hello@example.com']);
    $this->headers = ['Origin' => 'http://localhost', 'User-Agent' => 'Mozilla/5.0 Chrome/130'];
});

function sendContact(array $data = [])
{
    return test()->withHeaders(test()->headers)->postJson(route('api.contact'), [
        'topic' => 'general', 'name' => 'Aina Rahman', 'email' => 'aina@example.com',
        'message' => 'Hello! I would like to ask about your CakePHP workshop.', 'consent' => true, '_elapsed' => 6000, ...$data,
    ]);
}

it('renders the contact page with topic cards, the reply time and a ?topic= prefill', function () {
    updateSettings(ContactSettings::class, ['reply_time' => '2 working days']);

    $this->get('/contact?topic=speaking')->assertOk()
        ->assertSee(['What can I help with?', 'Speaking / Workshop', 'Usually replies within 2 working days', 'Before you ask', 'hello@example.com'])
        ->assertSee('\u0022topic\u0022:\u0022speaking\u0022', false); // @js() config for the form
    $this->get('/contact?topic=nonsense')->assertOk()->assertSee('\u0022topic\u0022:\u0022general\u0022', false);
});

it('stores a message and emails the owner (reply-to the sender) and the sender', function () {
    $article = Article::factory()->published()->create(['title' => 'Bake Basics']);

    sendContact(['topic' => 'tutorial', 'article_id' => $article->id, 'company' => 'Ignored Inc'])->assertOk()->assertJson(['name' => 'Aina Rahman']);

    $message = ContactMessage::sole();
    expect($message->status)->toBe('new')
        ->and($message->article_id)->toBe($article->id)
        ->and($message->company)->toBeNull() // only kept for topics that show the field
        ->and($message->consented_at)->not->toBeNull()
        ->and($message->visitor_hash)->toHaveLength(40);
    Mail::assertQueued(ContactReceived::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->hasReplyTo('aina@example.com'));
    Mail::assertQueued(ContactAutoReply::class, fn ($mail) => $mail->hasTo('aina@example.com'));

    updateSettings(ContactSettings::class, ['auto_reply' => false]);
    sendContact(['topic' => 'collaboration', 'company' => 'Pixel Sdn Bhd']);
    expect(ContactMessage::latest('id')->first()->company)->toBe('Pixel Sdn Bhd');
    Mail::assertQueued(ContactAutoReply::class, 1);
});

it('validates fields and requires consent', function () {
    sendContact(['email' => 'nope', 'message' => 'Too short', 'consent' => false, 'topic' => 'weird'])
        ->assertStatus(422)->assertJsonValidationErrors(['email', 'message', 'consent', 'topic']);
});

it('drops honeypot and too-fast sends silently and keeps spam out of the inbox mail', function () {
    sendContact(['website' => 'x'])->assertOk();
    sendContact(['_elapsed' => 900])->assertOk();
    expect(ContactMessage::count())->toBe(0);

    sendContact(['message' => 'Buy backlinks and SEO service today, best web traffic deal.'])->assertOk();
    expect(ContactMessage::sole()->status)->toBe('spam');
    Mail::assertNothingQueued();
});

it('allows three messages per hour per IP and refuses other origins', function () {
    foreach (range(1, 3) as $i) {
        sendContact(['message' => "Message number {$i} with enough text in it."])->assertOk();
    }
    sendContact()->assertStatus(429);

    $this->withHeaders(['Origin' => 'https://evil.example'])->postJson(route('api.contact'), [])->assertForbidden();
});

it('marks messages read when opened and replies from the inbox', function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
    $message = ContactMessage::create(['topic' => 'general', 'name' => 'Aina', 'email' => 'aina@example.com', 'message' => 'Hello there, a question.', 'consented_at' => now()]);

    Livewire::test(ListContactMessages::class)->assertCanSeeTableRecords([$message]);
    Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
        ->assertOk()
        ->callAction('reply', ['body' => 'Hi Aina, thanks for asking!'])
        ->assertHasNoActionErrors();

    $message->refresh();
    expect($message->status)->toBe('replied')->and($message->read_at)->not->toBeNull()->and($message->replies)->toHaveCount(1);
    Mail::assertQueued(ContactReplyMail::class, fn ($mail) => $mail->hasTo('aina@example.com') && $mail->hasReplyTo('hello@example.com'));
});

it('purges messages older than 12 months', function () {
    $old = ContactMessage::create(['topic' => 'general', 'name' => 'Old', 'email' => 'old@example.com', 'message' => 'An old message here.']);
    $old->forceFill(['created_at' => now()->subMonths(13)])->save();
    ContactMessage::create(['topic' => 'general', 'name' => 'New', 'email' => 'new@example.com', 'message' => 'A recent message here.']);

    $this->artisan('model:prune', ['--model' => [ContactMessage::class]])->assertSuccessful();

    expect(ContactMessage::pluck('name')->all())->toBe(['New']);
});
