{{--
    Large search box (home hero, 404). A plain GET form so it works without JS;
    the Ctrl/⌘ K palette takes over when it is available (data-search-trigger).
--}}
@props(['placeholder' => 'Search tutorials, e.g. “CakePHP authentication”', 'value' => null, 'palette' => true])
<form action="{{ url('/search') }}" method="get" role="search" {{ $attributes->class('relative') }} @if ($palette) data-search-trigger @endif>
    <label for="hero-search" class="sr-only">Search tutorials</label>
    <x-lucide-search class="pointer-events-none absolute top-1/2 left-5 size-5 -translate-y-1/2 text-muted-foreground" />
    <input id="hero-search" name="q" type="search" value="{{ $value }}" placeholder="{{ $placeholder }}" autocomplete="off"
        class="w-full rounded-full border border-border bg-card py-4 pr-28 pl-14 text-base text-foreground shadow-sm transition placeholder:text-muted-foreground focus:border-primary focus:ring-4 focus:ring-ring/20 focus:outline-none">
    <button type="submit" class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary-hover">Search</button>
</form>
