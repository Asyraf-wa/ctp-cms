{{-- Right column of the text-only home: Level, Popular tags, Popular this month, Learning paths. --}}
@props(['levels', 'tags', 'popular', 'series', 'idPrefix' => 'side'])
<div {{ $attributes->class('text-sm') }}>
    @if ($levels->isNotEmpty())
        <section aria-labelledby="{{ $idPrefix }}-levels">
            <h2 id="{{ $idPrefix }}-levels" class="mb-3 text-xs font-semibold tracking-wider text-foreground uppercase">Level</h2>
            <ul class="space-y-1">
                @foreach (\App\Enums\Difficulty::cases() as $level)
                    @continue(! $levels->get($level->value))
                    <li>
                        <a href="{{ url('/tutorials').'?difficulty='.$level->value }}" class="group flex items-center justify-between rounded-sm px-2 py-1.5 hover:bg-accent">
                            <span class="flex items-center gap-2">
                                <span @class([
                                    'size-2 rounded-full',
                                    'bg-beginner' => $level->value === 'beginner',
                                    'bg-intermediate' => $level->value === 'intermediate',
                                    'bg-advanced' => $level->value === 'advanced',
                                ]) aria-hidden="true"></span>
                                <span class="text-muted-foreground group-hover:text-foreground">{{ $level->getLabel() }}</span>
                            </span>
                            <span class="text-xs text-muted-foreground tabular-nums">{{ $levels->get($level->value) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($tags->isNotEmpty())
        <section aria-labelledby="{{ $idPrefix }}-tags">
            <h2 id="{{ $idPrefix }}-tags" class="mb-3 text-xs font-semibold tracking-wider text-foreground uppercase">Popular tags</h2>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($tags as $tag)
                    <a href="{{ url($tag->publicPath()) }}" class="rounded-sm border border-border px-2 py-0.5 text-xs font-medium text-muted-foreground transition-colors hover:border-primary hover:text-primary">#{{ $tag->name }}</a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($popular->isNotEmpty())
        <section aria-labelledby="{{ $idPrefix }}-popular">
            <h2 id="{{ $idPrefix }}-popular" class="mb-3 text-xs font-semibold tracking-wider text-foreground uppercase">Popular this month</h2>
            <ol class="space-y-3">
                @foreach ($popular as $article)
                    <li class="flex gap-3">
                        <span class="w-4 shrink-0 font-mono text-xs leading-5 font-semibold text-primary">{{ $loop->iteration }}</span>
                        <a href="{{ url($article->publicPath()) }}" class="leading-5 text-muted-foreground transition-colors hover:text-primary">{{ $article->title }}</a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($series->isNotEmpty())
        <section aria-labelledby="{{ $idPrefix }}-series">
            <h2 id="{{ $idPrefix }}-series" class="mb-3 text-xs font-semibold tracking-wider text-foreground uppercase">Learning paths</h2>
            <ul class="space-y-2">
                @foreach ($series as $item)
                    <li>
                        <a href="{{ url($item->publicPath()) }}" class="group flex items-start gap-2">
                            <x-lucide-list-ordered class="mt-0.5 size-4 shrink-0 text-muted-foreground group-hover:text-primary" />
                            <span>
                                <span class="block leading-5 text-foreground group-hover:text-primary">{{ $item->title }}</span>
                                <span class="text-xs text-muted-foreground">{{ $item->articles_count }} {{ Str::plural('part', $item->articles_count) }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
