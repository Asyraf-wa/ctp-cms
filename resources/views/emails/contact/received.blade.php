<x-mail::message>
# {{ $contact->topicLabel() }}

**{{ $contact->name }}** &lt;{{ $contact->email }}&gt;{{ $contact->company ? ' · '.$contact->company : '' }}

@if ($contact->article)
About: [{{ $contact->article->title }}]({{ url($contact->article->publicPath()) }})
@endif

<div class="quote">{!! nl2br(e($contact->message)) !!}</div>

<x-mail::button :url="$inboxUrl">
Open in the inbox
</x-mail::button>

<span class="muted">Reply to this email to answer {{ $contact->name }} directly, or reply from the inbox to keep a record.</span>
</x-mail::message>
