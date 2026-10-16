<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO-10: 301 to the canonical form of a public URL: lowercase, no trailing slash, no doubled slashes.
 * Leaves the admin, Livewire, files with extensions (media, build) and non-GET requests alone.
 */
class NormalizeUrl
{
    private const SKIP = '#^/(admin|livewire|filament|storage|media|build|og|api|auth)(/|$)#i';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo();
        if ($path === '/' || preg_match(self::SKIP, $path)) {
            return $next($request);
        }

        $clean = '/'.trim((string) preg_replace('#/{2,}#', '/', $path), '/');
        // Only extension-less paths are lowercased (file names may be case-sensitive).
        if (! str_contains(basename($clean), '.')) {
            $clean = strtolower($clean);
        }

        if ($clean !== $path) {
            // A known redirect for the clean path: go straight there (one hop, not a chain).
            $known = Redirect::findForPath($clean);
            if ($known && $known->to_path !== null) {
                return $known->toResponse();
            }
            $query = $request->getQueryString();

            return redirect()->to($clean.($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
