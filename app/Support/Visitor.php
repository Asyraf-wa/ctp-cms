<?php

namespace App\Support;

use Illuminate\Http\Request;

// ART-10, ART-12: an anonymous, non-reversible visitor id and a cheap bot filter.
class Visitor
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|scrap|headless|lighthouse|pagespeed|preview|facebookexternalhit|embedly|monitor|curl|wget|python|httpclient|http-client|java\/|go-http|okhttp|axios|node-fetch|phantom|puppeteer|playwright|selenium/i';

    public static function hash(Request $request): string
    {
        return hash_hmac('sha1', $request->ip().'|'.$request->userAgent(), (string) config('app.key'));
    }

    public static function isBot(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match(self::BOT_PATTERN, $agent) === 1;
    }

    // Beacons and kudos come from our own pages; anything else is ignored (no CSRF token on cached pages).
    public static function isSameOrigin(Request $request): bool
    {
        $origin = $request->headers->get('Origin') ?? $request->headers->get('Referer');

        return $origin !== null && parse_url($origin, PHP_URL_HOST) === $request->getHost();
    }
}
