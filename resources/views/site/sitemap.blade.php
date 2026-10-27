{{-- Sitemap for people: everything on one screen. The XML sitemap (/sitemap.xml) is for crawlers. --}}
<x-layouts.site :meta="$meta">
    <div class="mx-auto max-w-[1440px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
        <x-site.page-header title="Sitemap" :breadcrumbs="$meta->breadcrumbs" description="Every tutorial, blog post, series, project and page on one screen." />

        @php($link = 'inline-block py-1 text-sm text-muted-foreground transition-colors hover:text-primary')

        <div class="space-y-10">
            <section aria-labelledby="sm-main">
                <h2 id="sm-main" class="mb-3 text-lg font-bold text-foreground">Main sections</h2>
                <ul class="flex flex-wrap gap-x-6 gap-y-1">
                    <li><a href="{{ url('/') }}" class="{{ $link }}">Home</a></li>
                    <li><a href="{{ url('/tutorials') }}" class="{{ $link }}">All tutorials</a></li>
                    <li><a href="{{ url('/blog') }}" class="{{ $link }}">Blog</a></li>
                    <li><a href="{{ url('/projects') }}" class="{{ $link }}">Projects</a></li>
                    <li><a href="{{ url('/series') }}" class="{{ $link }}">Learning paths</a></li>
                    <li><a href="{{ url('/contact') }}" class="{{ $link }}">Contact</a></li>
                    @foreach ($pages as $page)
                        <li><a href="{{ url($page->publicPath() ?? '/') }}" class="{{ $link }}">{{ $page->title }}</a></li>
                    @endforeach
                </ul>
            </section>

            @if ($groups->isNotEmpty())
                <section aria-labelledby="sm-tutorials">
                    <h2 id="sm-tutorials" class="mb-4 text-lg font-bold text-foreground">Tutorials by category</h2>
                    <div class="grid gap-x-8 gap-y-8 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($groups as $group)
                            <div>
                                <h3 class="mb-1 font-semibold text-foreground">
                                    <a href="{{ url($group['category']->publicPath()) }}" class="inline-block py-1 hover:text-primary">{{ $group['category']->name }}</a>
                                    <span class="text-xs font-normal text-muted-foreground">({{ $group['articles']->count() }})</span>
                                </h3>
                                <ul>
                                    @foreach ($group['articles'] as $article)
                                        <li><a href="{{ url($article->publicPath() ?? '/') }}" class="{{ $link }}">{{ $article->title }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($series->isNotEmpty())
                <section aria-labelledby="sm-series">
                    <h2 id="sm-series" class="mb-3 text-lg font-bold text-foreground">Learning paths</h2>
                    <ul>
                        @foreach ($series as $item)
                            <li><a href="{{ url($item->publicPath()) }}" class="{{ $link }}">{{ $item->title }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($posts->isNotEmpty())
                <section aria-labelledby="sm-blog">
                    <h2 id="sm-blog" class="mb-3 text-lg font-bold text-foreground">Blog</h2>
                    <ul>
                        @foreach ($posts as $post)
                            <li><a href="{{ url($post->publicPath() ?? '/') }}" class="{{ $link }}">{{ $post->title }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($projects->isNotEmpty())
                <section aria-labelledby="sm-projects">
                    <h2 id="sm-projects" class="mb-3 text-lg font-bold text-foreground">Projects</h2>
                    <ul>
                        @foreach ($projects as $project)
                            <li><a href="{{ url($project->publicPath()) }}" class="{{ $link }}">{{ $project->title }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="sm-feeds">
                <h2 id="sm-feeds" class="mb-3 text-lg font-bold text-foreground">Feeds and machine-readable</h2>
                <ul class="flex flex-wrap gap-x-6 gap-y-1">
                    <li><a href="{{ url('/feed') }}" class="{{ $link }}">RSS feed</a></li>
                    <li><a href="{{ url('/feed/blog') }}" class="{{ $link }}">Blog feed</a></li>
                    <li><a href="{{ url('/sitemap.xml') }}" class="{{ $link }}">XML sitemap</a></li>
                    <li><a href="{{ url('/llms.txt') }}" class="{{ $link }}">llms.txt</a></li>
                </ul>
            </section>
        </div>
    </div>
</x-layouts.site>
