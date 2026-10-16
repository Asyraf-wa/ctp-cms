<?php

namespace App\Support\Spam;

use Illuminate\Http\Request;

/**
 * Shared checks for public forms (CMT-02, CONTACT-04). Each check returns a reason or null.
 * "Silent" reasons (honeypot, too fast) get a fake success so bots learn nothing.
 */
class SpamGuard
{
    public const HONEYPOT = 'website';

    public const ELAPSED = '_elapsed';

    public const SILENT = ['honeypot', 'too fast'];

    private const KEYWORDS = ['viagra', 'cialis', 'casino', 'porn', 'escort', 'payday loan', 'seo service', 'backlinks', 'crypto signal', 'forex signal', 'buy followers', 'web traffic', 'guest post'];

    public static function check(Request $request, string $text, int $minSeconds = 3, int $maxLinks = 2): ?string
    {
        if (filled($request->input(self::HONEYPOT))) {
            return 'honeypot';
        }
        // Milliseconds between the form appearing and the submit, measured in the browser.
        if ((int) $request->input(self::ELAPSED, 0) < $minSeconds * 1000) {
            return 'too fast';
        }
        if (Turnstile::siteKey() && ! Turnstile::verify($request->input('cf-turnstile-response'), $request->ip())) {
            return 'turnstile';
        }
        if (preg_match_all('#https?://|www\.#i', $text) > $maxLinks) {
            return 'too many links';
        }
        $lower = mb_strtolower($text);
        foreach (self::KEYWORDS as $word) {
            if (str_contains($lower, $word)) {
                return 'keyword: '.$word;
            }
        }

        return null;
    }
}
