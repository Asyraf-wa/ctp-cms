<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO-10: 301 to the canonical form of a public URL: lowercase, no trailing slash, no doubled slashes,
 * and never under the host's app folder.
 * Leaves the admin, Livewire, files with extensions (media, build) and non-GET requests alone.
 */
class NormalizeUrl
{
    // /codethepixel/public, then the rest of the path and the query string. (/codethepixel/… alone is the legacy
    // CakePHP site: those URLs have their own redirects.)
    private const HOST_FOLDER = '#^/codethepixel/public(?<rest>(?:[/?].*)?)$#i';

    private const SKIP = '#^/(admin|livewire|filament|storage|media|build|og|api|auth)(/|$)#i';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        // The app also answers under the folder it is served from on the host: send that to the real URL,
        // or every page would exist twice for search engines.
        if (preg_match(self::HOST_FOLDER, $request->getRequestUri(), $folder)) {
            return redirect()->away(rtrim((string) config('app.url'), '/').'/'.ltrim($folder['rest'], '/'), 301);
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
            // A known redirect (or 410) for the clean path: answer it now (one hop, not a chain).
            if ($known = Redirect::findForPath($clean)) {
                return $known->toResponse();
            }
            $query = $request->getQueryString();

            return redirect()->to($clean.($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}
