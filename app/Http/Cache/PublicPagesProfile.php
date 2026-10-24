<?php

namespace App\Http\Cache;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * OPS-04: cache what guests read (pages, feeds, sitemaps, Markdown), nothing personal or interactive.
 * Unknown query strings are not cached, so random ?x= values cannot fill the cache.
 */
class PublicPagesProfile extends CacheAllSuccessfulGetRequests
{
    private const SKIP = '#^/(admin|livewire|filament|api|preview|search|og|auth|comments|storage|build|media|up)(/|$)#';

    // Query parameters that select real variants of a page.
    private const QUERY = ['page', 'category', 'tag', 'difficulty', 'sort', 'topic', 'tech', 'article'];

    private const TYPES = ['text/html', 'text/plain', 'text/markdown', 'text/xml', 'application/xml', 'application/rss+xml', 'application/atom+xml'];

    public function shouldCacheRequest(Request $request): bool
    {
        if (! parent::shouldCacheRequest($request) || Auth::check() || preg_match(self::SKIP, $request->getPathInfo())) {
            return false;
        }

        $ignored = config('responsecache.ignored_query_parameters', []);

        return array_diff(array_keys($request->query()), self::QUERY, $ignored) === [];
    }

    public function shouldCacheResponse(Response $response): bool
    {
        $type = strtolower(trim(explode(';', (string) $response->headers->get('Content-Type'))[0]));

        return $response->getStatusCode() === 200 && in_array($type, self::TYPES, true);
    }

    // Part of every cache key: a new release (the Vite manifest changes) and the scheme each get their own entries.
    // Without it, cached HTML points at asset files the new release deleted (unstyled pages after a deploy), and a
    // page built over http is served to https visitors. Old entries simply expire.
    public function useCacheNameSuffix(Request $request): string
    {
        static $release = null;
        $release ??= (string) @filemtime(public_path('build/manifest.json'));

        return $release.'|'.$request->getScheme();
    }
}
