<x-mail::message>
Hi {{ $reply->message->name }},

{!! \App\Support\Html::comment($reply->body) !!}

{{ $reply->user?->authorName() ?? $site }}

<div class="quote" style="margin-top: 24px;">
<p class="muted">Your message, {{ $reply->message->created_at->timezone(config('ctp.display_timezone'))->format('j M Y') }}:</p>
{!! nl2br(e(\Illuminate\Support\Str::limit($reply->message->message, 1200))) !!}
</div>
</x-mail::message>
