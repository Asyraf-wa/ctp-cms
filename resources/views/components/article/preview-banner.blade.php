@props(['article'])
<div class="sticky top-0 z-[60] bg-[#854d0e] px-4 py-2 text-center text-sm font-medium text-white">
    Preview · {{ $article->status->getLabel() }}{{ $article->published_at ? ' for '.$article->published_at->timezone(config('ctp.display_timezone'))->format('j M Y, g:i a') : '' }} · This page is not public and not indexed.
</div>
