<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * OPS-05: security headers on every response, and a Content-Security-Policy on public pages.
 *
 * The CSP allows 'unsafe-eval' (Alpine evaluates its directives) and 'unsafe-inline' (the theme
 * script in <head>, JSON-LD and scripts pasted in Site settings → Scripts). Hosts for analytics
 * pasted there go in CSP_EXTRA_HOSTS. The admin gets the headers but no CSP: Filament and
 * Livewire need a looser policy and the admin is behind a login.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // PHP adds this itself; it only tells attackers which version to look up.
        header_remove('X-Powered-By');

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $type = (string) $headers->get('Content-Type');
        if (! preg_match('#^/(admin|livewire|filament)(/|$)#', $request->getPathInfo()) && ($type === '' || str_starts_with($type, 'text/html'))) {
            $headers->set('Content-Security-Policy', $this->policy());
        }

        return $response;
    }

    private function policy(): string
    {
        $extra = preg_split('/\s+/', trim((string) config('ctp.csp.extra_hosts'))) ?: [];
        $dev = [];
        if (Vite::isRunningHot()) {
            // The Vite dev server and its hot-reload websocket.
            $origin = rtrim(trim((string) @file_get_contents(public_path('hot'))), '/');
            $dev = [$origin, preg_replace('#^http#', 'ws', $origin)];
        }

        $src = fn (array $sources) => implode(' ', array_values(array_unique(array_filter($sources))));

        return implode('; ', [
            "default-src 'self'",
            'script-src '.$src(["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://challenges.cloudflare.com', ...$extra, ...$dev]),
            'style-src '.$src(["'self'", "'unsafe-inline'", ...$dev]),
            'img-src '.$src(["'self'", 'data:', 'blob:', 'https:']),
            'font-src '.$src(["'self'", 'data:', ...$dev]),
            'connect-src '.$src(["'self'", 'https://challenges.cloudflare.com', ...$extra, ...$dev]),
            'frame-src '.$src(['https://www.youtube-nocookie.com', 'https://codepen.io', 'https://challenges.cloudflare.com', ...$extra]),
            'media-src '.$src(["'self'", 'https:']),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            ...(app()->isProduction() ? ['upgrade-insecure-requests'] : []),
        ]);
    }
}
