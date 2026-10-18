{{--
    GEO-07: copy the page as Markdown, open the .md, or hand the page to an AI assistant.
    The assistants get the .md URL, which is cleaner to read than the HTML page.
--}}
@props(['article', 'compact' => false])
@php
    $md = url($article->publicPath()).'.md';
    $prompt = 'Read '.$md.' and help me follow this tutorial. Answer questions about it and adapt the steps to my setup.';
    $links = [
        ['ChatGPT', 'https://chatgpt.com/?hints=search&q='.rawurlencode($prompt), 'bot'],
        ['Claude', 'https://claude.ai/new?q='.rawurlencode($prompt), 'sparkles'],
    ];
@endphp
<div {{ $attributes->class($compact ? 'flex flex-wrap gap-2' : 'space-y-1 text-sm') }} x-data="{ copied: false }">
    <button type="button"
        @click="fetch(@js($md)).then(r => r.text()).then(t => navigator.clipboard.writeText(t)).then(() => { copied = true; setTimeout(() => copied = false, 1600) }).catch(() => window.open(@js($md), '_blank'))"
        @class(['inline-flex items-center gap-2 text-muted-foreground transition-colors hover:text-primary', 'rounded-md border border-border bg-card px-3 py-1.5 text-sm' => $compact, 'w-full py-1' => ! $compact])>
        <x-lucide-clipboard-copy class="size-4 shrink-0" x-show="!copied" />
        <x-lucide-check class="size-4 shrink-0 text-primary" x-show="copied" x-cloak />
        <span x-text="copied ? 'Copied as Markdown' : 'Copy page as Markdown'">Copy page as Markdown</span>
    </button>
    <a href="{{ $md }}" target="_blank" rel="noopener" @class(['inline-flex items-center gap-2 text-muted-foreground transition-colors hover:text-primary', 'rounded-md border border-border bg-card px-3 py-1.5 text-sm' => $compact, 'w-full py-1' => ! $compact])>
        <x-lucide-file-text class="size-4 shrink-0" /> View as Markdown
    </a>
    @foreach ($links as [$name, $href, $icon])
        <a href="{{ $href }}" target="_blank" rel="noopener nofollow" @class(['inline-flex items-center gap-2 text-muted-foreground transition-colors hover:text-primary', 'rounded-md border border-border bg-card px-3 py-1.5 text-sm' => $compact, 'w-full py-1' => ! $compact])>
            <x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" /> Ask {{ $name }} about this page
        </a>
    @endforeach
</div>
