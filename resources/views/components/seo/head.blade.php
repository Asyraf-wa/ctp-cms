{{-- SEO-01, SEO-04: everything search engines and social networks read, from one Meta. --}}
@props(['meta'])
@inject('general', 'App\Settings\GeneralSettings')
@inject('seo', 'App\Settings\SeoSettings')
@inject('social', 'App\Settings\SocialSettings')
@inject('builder', 'App\Seo\Seo')
@php
    $pageTitle = $meta->title
        ? strtr($seo->title_format, ['{page}' => $meta->title, '{site}' => $general->site_name])
        : ($seo->meta_title ?: $general->site_name);
    $description = $meta->description ?: $seo->meta_description;
    $handle = $social->x ? '@'.ltrim((string) parse_url($social->x, PHP_URL_PATH), '/') : null;
@endphp
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $meta->robots ?: $seo->default_robots }}">
@if ($meta->canonical)
    <link rel="canonical" href="{{ $meta->canonical }}">
@endif
@if ($meta->prevUrl)
    <link rel="prev" href="{{ $meta->prevUrl }}">
@endif
@if ($meta->nextUrl)
    <link rel="next" href="{{ $meta->nextUrl }}">
@endif
@if ($meta->markdownUrl)
    <link rel="alternate" type="text/markdown" href="{{ $meta->markdownUrl }}" title="Markdown">
@endif
<link rel="alternate" type="application/rss+xml" href="{{ url('/feed') }}" title="{{ $general->site_name }}">
@if ($seo->meta_keywords && ! $meta->tags)
    <meta name="keywords" content="{{ $seo->meta_keywords }}">
@elseif ($meta->tags)
    <meta name="keywords" content="{{ implode(', ', $meta->tags) }}">
@endif
@if ($meta->author ?: $seo->meta_author)
    <meta name="author" content="{{ $meta->author ?: $seo->meta_author }}">
@endif
@if ($seo->meta_copyright)
    <meta name="copyright" content="{{ $seo->meta_copyright }}">
@endif
@if ($seo->google_verification)
    <meta name="google-site-verification" content="{{ $seo->google_verification }}">
@endif
@if ($seo->bing_verification)
    <meta name="msvalidate.01" content="{{ $seo->bing_verification }}">
@endif

{{-- Open Graph / Twitter --}}
<meta property="og:site_name" content="{{ $general->site_name }}">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="{{ $meta->type }}">
<meta property="og:title" content="{{ $meta->title ?: ($seo->meta_title ?: $general->site_name) }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $meta->canonical ?: url()->current() }}">
@if ($meta->image)
    <meta property="og:image" content="{{ $meta->image }}">
    @if ($meta->imageWidth)
        <meta property="og:image:width" content="{{ $meta->imageWidth }}">
        <meta property="og:image:height" content="{{ $meta->imageHeight }}">
    @endif
    @if ($meta->imageAlt)
        <meta property="og:image:alt" content="{{ $meta->imageAlt }}">
    @endif
@endif
@if ($meta->type === 'article')
    @if ($meta->publishedAt)
        <meta property="article:published_time" content="{{ $meta->publishedAt->toAtomString() }}">
    @endif
    @if ($meta->modifiedAt)
        <meta property="article:modified_time" content="{{ $meta->modifiedAt->toAtomString() }}">
    @endif
    @if ($meta->section)
        <meta property="article:section" content="{{ $meta->section }}">
    @endif
    @foreach ($meta->tags as $tag)
        <meta property="article:tag" content="{{ $tag }}">
    @endforeach
@endif
<meta name="twitter:card" content="summary_large_image">
@if ($handle)
    <meta name="twitter:site" content="{{ $handle }}">
@endif

<script type="application/ld+json">{!! json_encode($builder->graph($meta), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
