<?php

namespace App\Seo;

use App\Models\Media;
use Illuminate\Support\Str;

/**
 * SEO-02: quick on-page checks shown in the editor. Works on the unsaved form state (TipTap JSON).
 */
class SeoChecklist
{
    /**
     * @param  array<string, mixed>|null  $body
     * @return list<array{ok: bool, label: string, hint: string}>
     */
    public static function run(?string $title, ?string $description, ?string $slug, ?array $body, bool $hasBody = true): array
    {
        $titleLength = mb_strlen((string) $title);
        $descriptionLength = mb_strlen((string) $description);
        $checks = [
            ['ok' => $titleLength >= 30 && $titleLength <= 60, 'label' => "Title length ({$titleLength})", 'hint' => '30–60 characters show in full on Google.'],
            ['ok' => $descriptionLength >= 70 && $descriptionLength <= 160, 'label' => "Description length ({$descriptionLength})", 'hint' => '70–160 characters. Uses the excerpt when the SEO description is empty.'],
            ['ok' => self::slugHasKeyword((string) $title, (string) $slug), 'label' => 'Keyword in the URL', 'hint' => 'The slug should contain a main word from the title.'],
        ];

        if ($hasBody) {
            $stats = self::walk($body ?? []);
            $checks[] = ['ok' => $stats['h1'] === 0, 'label' => 'One H1 only', 'hint' => 'The title is the H1; use H2 and H3 inside the content.'];
            $checks[] = ['ok' => $stats['h2'] > 0, 'label' => 'Sections with H2 headings', 'hint' => 'Headings build the “On this page” list and help readers scan.'];
            $checks[] = ['ok' => $stats['images'] === 0 || $stats['missingAlt'] === 0, 'label' => $stats['missingAlt'] ? "Alt text on images ({$stats['missingAlt']} missing)" : 'Alt text on images', 'hint' => 'Describe each image for screen readers and image search.'];
            $checks[] = ['ok' => $stats['internalLinks'] > 0, 'label' => 'Links to other tutorials', 'hint' => 'At least one internal link helps readers and search engines.'];
            $checks[] = ['ok' => $stats['words'] >= 300, 'label' => "Length ({$stats['words']} words)", 'hint' => 'Under 300 words is thin content unless it is a quick tip.'];
        }

        return $checks;
    }

    private static function slugHasKeyword(string $title, string $slug): bool
    {
        $words = collect(preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($title))) ?: [])
            ->filter(fn ($w) => strlen($w) >= 4 && ! in_array($w, ['with', 'from', 'your', 'that', 'this', 'into', 'using', 'what', 'when', 'how'], true));

        return $slug !== '' && $words->contains(fn ($w) => str_contains($slug, $w));
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{h1: int, h2: int, images: int, missingAlt: int, internalLinks: int, words: int}
     */
    private static function walk(array $node): array
    {
        $stats = ['h1' => 0, 'h2' => 0, 'images' => 0, 'missingAlt' => 0, 'internalLinks' => 0, 'words' => 0];
        $host = parse_url(url('/'), PHP_URL_HOST);

        $visit = function (array $node) use (&$visit, &$stats, $host) {
            $type = $node['type'] ?? null;
            if ($type === 'heading') {
                $level = (int) ($node['attrs']['level'] ?? 2);
                $stats['h1'] += $level === 1 ? 1 : 0;
                $stats['h2'] += $level === 2 ? 1 : 0;
            }
            if ($type === 'image' || ($type === 'customBlock' && ($node['attrs']['id'] ?? null) === 'image')) {
                $stats['images']++;
                $alt = $type === 'image' ? ($node['attrs']['alt'] ?? null) : ($node['attrs']['config']['alt'] ?? null);
                $mediaId = $type === 'image' ? ($node['attrs']['id'] ?? null) : ($node['attrs']['config']['media_id'] ?? null);
                if (blank($alt) && ! (is_numeric($mediaId) && filled(Media::whereKey($mediaId)->value('alt')))) {
                    $stats['missingAlt']++;
                }
            }
            if ($type === 'text') {
                $stats['words'] += str_word_count((string) ($node['text'] ?? ''));
                foreach ($node['marks'] ?? [] as $mark) {
                    $href = (string) ($mark['attrs']['href'] ?? '');
                    if (($mark['type'] ?? null) === 'link' && (str_starts_with($href, '/') || parse_url($href, PHP_URL_HOST) === $host)) {
                        $stats['internalLinks']++;
                    }
                }
            }
            foreach ($node['content'] ?? [] as $child) {
                $visit($child);
            }
        };
        $visit($node);

        return $stats;
    }
}
