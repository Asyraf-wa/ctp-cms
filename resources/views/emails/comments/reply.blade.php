<x-mail::message>
# New reply to your comment

**{{ $reply->name }}**{{ $reply->user_id ? ' (author)' : '' }} replied on **{{ $reply->article->title }}**:

<div class="quote">{!! $reply->body_html !!}</div>

<x-mail::button :url="$reply->url()">
Read the conversation
</x-mail::button>

<span class="muted">You asked to be told about replies to your comment. [Stop these emails]({{ $unsubscribeUrl }}).</span>
</x-mail::message>
