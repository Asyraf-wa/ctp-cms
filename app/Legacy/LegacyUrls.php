<?php

namespace App\Legacy;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * MIG-05: old listing URLs that carry their meaning in the query string, which path redirects cannot see:
 * /codethepixel/articles?tag=cakephp → /tag/cakephp, ?category=1 → that category, anything else → /tutorials.
 */
class LegacyUrls
{
    private const LISTINGS = ['/codethepixel', '/codethepixel/articles', '/codethepixel/articles/index', '/articles', '/articles/index'];

    public static function redirectFor(Request $request): ?RedirectResponse
    {
        $path = '/'.trim(Str::lower($request->getPathInfo()), '/');
        if (! in_array($path, self::LISTINGS, true) || $request->query() === []) {
            return null;
        }

        if ($request->filled('tag')) {
            $wanted = Str::slug((string) $request->query('tag'));
            $tag = Tag::where('slug', $wanted)->first()
                ?? Tag::all(['id', 'name', 'slug'])->first(fn (Tag $t) => self::key($t->name) === self::key((string) $request->query('tag')));

            return redirect($tag ? (string) $tag->publicPath() : '/tutorials', 301);
        }
        if ($request->filled('category') && ($category = Category::where('legacy_id', (int) $request->query('category'))->first())) {
            return redirect((string) $category->publicPath(), 301);
        }

        return redirect($path === '/codethepixel' ? '/' : '/tutorials', 301);
    }

    private static function key(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($value)));
    }
}
