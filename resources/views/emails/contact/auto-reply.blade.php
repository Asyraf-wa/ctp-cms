<x-mail::message>
# Thanks, {{ $contact->name }}

Your message reached {{ $site }}. You can expect a reply within **{{ $replyTime }}**.

Here is a copy of what you sent:

<div class="quote">{!! nl2br(e($contact->message)) !!}</div>

While you wait, the tutorials might already have the answer:

<x-mail::button :url="url('/tutorials')">
Browse tutorials
</x-mail::button>

<span class="muted">You received this because you used the contact form on {{ parse_url(config('app.url'), PHP_URL_HOST) }}. No need to reply to this email.</span>
</x-mail::message>
