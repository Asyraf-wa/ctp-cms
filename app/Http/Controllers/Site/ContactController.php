<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\ContactAutoReply;
use App\Mail\ContactReceived;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Seo\Seo;
use App\Settings\ContactSettings;
use App\Settings\GeneralSettings;
use App\Settings\SocialSettings;
use App\Support\CommentNotifier;
use App\Support\Spam\SpamGuard;
use App\Support\Spam\Turnstile;
use App\Support\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

// PRD CONTACT-01..07.
class ContactController extends Controller
{
    public const MIN_LENGTH = 20;

    public const MAX_LENGTH = 5000;

    public const PER_HOUR = 3;

    public function show(Request $request, ContactSettings $contact, GeneralSettings $general, SocialSettings $social): View
    {
        $topic = array_key_exists((string) $request->query('topic'), ContactMessage::TOPICS) ? (string) $request->query('topic') : 'general';

        return view('site.contact', [
            'meta' => app(Seo::class)->basic('Contact', 'Questions about a tutorial, collaborations and workshops: get in touch with '.$general->site_name.'.'),
            'topics' => ContactMessage::TOPICS,
            'topic' => $topic,
            'settings' => $contact,
            'email' => $general->contact_email,
            'socials' => $social->links(),
            'turnstileKey' => Turnstile::siteKey(),
            'popular' => Article::published()->tutorials()->forCards()->orderByDesc('views')->limit(3)->get(),
            // A tutorial picked by ?article= (e.g. from "Report a problem" links on articles).
            'article' => $request->filled('article') ? Article::published()->where('slug', (string) $request->query('article'))->first(['id', 'title', 'slug', 'type']) : null,
        ]);
    }

    public function store(Request $request, ContactSettings $settings): JsonResponse
    {
        abort_unless(Visitor::isSameOrigin($request), 403);

        $key = 'contact|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
            return response()->json(['message' => 'You have sent a few messages already. Please try again in an hour, or email directly.'], 429);
        }

        $topic = (string) $request->input('topic');
        $data = $request->validate([
            'topic' => ['required', Rule::in(array_keys(ContactMessage::TOPICS))],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:120'],
            'article_id' => ['nullable', 'integer', Rule::exists('articles', 'id')],
            'message' => ['required', 'string', 'min:'.self::MIN_LENGTH, 'max:'.self::MAX_LENGTH],
            'consent' => ['accepted'],
        ], [
            'message.min' => 'Please write at least '.self::MIN_LENGTH.' characters so I can help.',
            'consent.accepted' => 'Please agree to the privacy policy so I can store and answer your message.',
        ]);

        $reason = SpamGuard::check($request, $data['message'].' '.($data['company'] ?? ''), minSeconds: 3, maxLinks: 3);
        if ($reason === 'turnstile') {
            return response()->json(['message' => 'Please complete the “I am human” check and try again.'], 422);
        }
        if (in_array($reason, SpamGuard::SILENT, true)) {
            return response()->json(['name' => $data['name']]);
        }

        $message = ContactMessage::create([
            'topic' => $data['topic'],
            'name' => $data['name'],
            'email' => $data['email'],
            'company' => (ContactMessage::TOPICS[$topic]['company'] ?? false) ? ($data['company'] ?? null) : null,
            'article_id' => (ContactMessage::TOPICS[$topic]['article'] ?? false) ? ($data['article_id'] ?? null) : null,
            'message' => $data['message'],
            'status' => $reason ? ContactMessage::SPAM : ContactMessage::NEW,
            'spam_reason' => $reason,
            'visitor_hash' => Visitor::hash($request),
            'consented_at' => now(),
        ]);

        if (! $reason) {
            RateLimiter::hit($key, 3600);
            if ($to = CommentNotifier::ownerAddress()) {
                Mail::to($to)->queue(new ContactReceived($message));
            }
            if ($settings->auto_reply) {
                Mail::to($message->email, $message->name)->queue(new ContactAutoReply($message));
            }
        }

        return response()->json(['name' => $message->name]);
    }
}
