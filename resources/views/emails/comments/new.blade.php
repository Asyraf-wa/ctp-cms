<x-mail::message>
# {{ $comment->status === 'approved' ? 'New comment' : 'A comment is waiting for you' }}

**{{ $comment->name }}** ({{ $comment->email }}) commented on **[{{ $comment->article->title }}]({{ url($comment->article->publicPath()) }})**{{ $comment->parent ? ', replying to '.$comment->parent->name : '' }}:

<div class="quote">{!! $comment->body_html !!}</div>

@if ($comment->status === 'approved')
It is already live because {{ $comment->name }} has an approved comment before.
@endif

<x-mail::button :url="$moderateUrl">
{{ $comment->status === 'approved' ? 'View comments' : 'Moderate' }}
</x-mail::button>

<span class="muted">Reply to this email to answer {{ $comment->name }} privately.</span>
</x-mail::message>
