<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Brand logos from Simple Icons (CC0), shipped by codeat3/blade-simple-icons.
 * Titles and brand colours come from resources/data/simple-icons.json (npm run icons:data).
 */
class SimpleIcons
{
    /** @var array<string, array{0: string, 1: string}>|null */
    private static ?array $data = null;

    /** @return array<string, array{0: string, 1: string}> slug => [title, hex] */
    public static function all(): array
    {
        return self::$data ??= json_decode((string) file_get_contents(resource_path('data/simple-icons.json')), true) ?: [];
    }

    /** @return array{slug: string, title: string, hex: string}|null */
    public static function find(?string $slug): ?array
    {
        $icon = $slug ? (self::all()[$slug] ?? null) : null;

        return $icon ? ['slug' => (string) $slug, 'title' => $icon[0], 'hex' => $icon[1]] : null;
    }

    /**
     * Best matches for an admin search, exact and prefix matches first.
     *
     * @return array<string, string> slug => title
     */
    public static function search(string $query, int $limit = 40): array
    {
        $needle = self::normalise($query);
        if ($needle === '') {
            return [];
        }

        $scored = [];
        foreach (self::all() as $slug => [$title]) {
            $name = self::normalise($title);
            $score = match (true) {
                $name === $needle || $slug === $needle => 0,
                str_starts_with($name, $needle) || str_starts_with($slug, $needle) => 1,
                str_contains($name, $needle) => 2,
                default => null,
            };
            if ($score !== null) {
                $scored[$slug] = [$score, strlen($title), $title];
            }
        }

        uasort($scored, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_map(fn ($s) => $s[2], array_slice($scored, 0, $limit, true));
    }

    // The icon whose title or slug equals the name, e.g. "Joomla!" → joomla, "Vue.js" → vuedotjs.
    public static function match(string $name): ?string
    {
        $needle = self::normalise($name);
        foreach (self::all() as $slug => [$title]) {
            if ($slug === $needle || self::normalise($title) === $needle) {
                return $slug;
            }
        }

        return null;
    }

    private static function normalise(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii($value)));
    }
}
