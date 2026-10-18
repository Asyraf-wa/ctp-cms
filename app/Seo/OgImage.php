<?php

namespace App\Seo;

use App\Enums\ArticleType;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use App\Models\Series;
use App\Settings\GeneralSettings;
use GdImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * SEO-03: 1200×630 social cards drawn with GD (no Node or Chrome on shared hosting).
 * The URL carries a hash of the card's text, so a new title means a new file and no stale caches.
 */
class OgImage
{
    // Bump to regenerate every card after a design change.
    public const VERSION = 2;

    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** @var array<string, class-string<Model>> */
    public const TYPES = [
        'article' => Article::class,
        'page' => Page::class,
        'project' => Project::class,
        'category' => Category::class,
        'series' => Series::class,
    ];

    public static function url(string $type, ?Model $model): string
    {
        return route('og', ['key' => self::key($type, $model)]);
    }

    public static function key(string $type, ?Model $model): string
    {
        return $type.'-'.($model?->getKey() ?? 0).'-'.substr(md5(json_encode(self::content($type, $model)).'|'.self::VERSION), 0, 8);
    }

    /** @return array{eyebrow: string, title: string, footer: string} */
    public static function content(string $type, ?Model $model): array
    {
        $general = app(GeneralSettings::class);

        return match (true) {
            $model instanceof Article => [
                'eyebrow' => $model->type === ArticleType::Blog ? 'Blog' : ($model->category->name ?? 'Tutorial'),
                'title' => $model->title,
                'footer' => collect([$model->author?->authorName(), $model->technologies->pluck('name')->take(3)->join(' · ') ?: null])->filter()->join('  ·  '),
            ],
            $model instanceof Project => ['eyebrow' => 'Project', 'title' => $model->title, 'footer' => (string) $model->tagline],
            $model instanceof Category => ['eyebrow' => 'Tutorials', 'title' => $model->name, 'footer' => (string) $model->description],
            $model instanceof Series => ['eyebrow' => 'Learning path', 'title' => $model->title, 'footer' => (string) $model->description],
            $model instanceof Page => ['eyebrow' => $general->site_name, 'title' => $model->title, 'footer' => (string) $model->excerpt],
            default => ['eyebrow' => $general->site_name, 'title' => $general->slogan ?: $general->site_name, 'footer' => (string) $general->description],
        };
    }

    /** PNG bytes for the card. */
    public function render(string $type, ?Model $model): string
    {
        $content = self::content($type, $model);
        $general = app(GeneralSettings::class);
        $font = fn (string $weight) => resource_path("fonts/Inter-{$weight}.ttf");

        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($img, true);
        imagefill($img, 0, 0, $this->color($img, '#0b1220'));

        // Soft teal glow, top right and bottom left.
        $this->glow($img, 1050, 60, 620, '#14b8a6', 0.20);
        $this->glow($img, 80, 640, 520, '#0f766e', 0.22);

        // Faint grid, like the cover fallback on the site.
        $grid = $this->color($img, '#ffffff', 0.035);
        for ($x = 0; $x < self::WIDTH; $x += 40) {
            imageline($img, $x, 0, $x, self::HEIGHT, $grid);
        }
        for ($y = 0; $y < self::HEIGHT; $y += 40) {
            imageline($img, 0, $y, self::WIDTH, $y, $grid);
        }

        $left = 80;

        // Brand: the </> glyph (Victor Mono's ligature, pre-rendered: GD cannot shape ligatures) and the site name.
        $this->mark($img, $left, 76, 84);
        // Same face as the navbar (Titillium Web Bold, converted from the Latin WOFF: GD needs TTF).
        $this->text($img, $general->site_name, 32, resource_path('fonts/TitilliumWeb-Bold.ttf'), $left + 108, 110, '#e2e8f0');

        // Eyebrow and title (auto-sized to at most three lines).
        $this->text($img, Str::upper(Str::limit($content['eyebrow'], 40, '…')), 22, $font('SemiBold'), $left, 236, '#5eead4', tracking: 2);
        [$size, $lines] = $this->fit($content['title'], $font('ExtraBold'), self::WIDTH - 2 * $left, [64, 58, 52, 46, 40], 3);
        $y = 236 + (int) round($size * 1.55);
        foreach ($lines as $line) {
            $this->text($img, $line, $size, $font('ExtraBold'), $left, $y, '#ffffff');
            $y += (int) round($size * 1.22);
        }

        // Footer: domain left, context right; teal bar along the bottom.
        $domain = $general->domain_name ?: (string) parse_url(config('app.url'), PHP_URL_HOST);
        $this->text($img, $domain, 22, $font('Regular'), $left, 566, '#94a3b8');
        if ($content['footer'] !== '') {
            [, $footer] = $this->fit($content['footer'], $font('Regular'), 640, [22], 1);
            $this->text($img, $footer[0], 22, $font('Regular'), self::WIDTH - $left, 566, '#cbd5e1', right: true);
        }
        imagefilledrectangle($img, 0, self::HEIGHT - 10, self::WIDTH, self::HEIGHT, $this->color($img, '#14b8a6'));

        ob_start();
        imagepng($img, null, 6);

        return (string) ob_get_clean();
    }

    private function mark(GdImage $img, int $x, int $y, int $width): void
    {
        $mark = imagecreatefrompng(resource_path('brand/mark-og.png'));
        if ($mark === false) {
            return;
        }

        $height = (int) round($width * imagesy($mark) / imagesx($mark));
        imagecopyresampled($img, $mark, $x, $y, 0, 0, $width, $height, imagesx($mark), imagesy($mark));
    }

    /**
     * Largest size from $sizes whose wrapped text fits in $maxLines; the last line is ellipsised if needed.
     *
     * @param  list<int>  $sizes
     * @return array{0: int, 1: list<string>}
     */
    private function fit(string $text, string $font, int $width, array $sizes, int $maxLines): array
    {
        foreach ($sizes as $size) {
            $lines = $this->wrap($text, $font, $size, $width);
            if (count($lines) <= $maxLines) {
                return [$size, $lines];
            }
        }

        $size = end($sizes);
        $lines = array_slice($this->wrap($text, $font, $size, $width), 0, $maxLines);
        $last = $lines[$maxLines - 1];
        while ($last !== '' && $this->width($last.'…', $font, $size) > $width) {
            $last = rtrim(Str::beforeLast($last, ' ') === $last ? mb_substr($last, 0, -1) : Str::beforeLast($last, ' '));
        }
        $lines[$maxLines - 1] = $last.'…';

        return [$size, $lines];
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, int $size, int $width): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && $this->width($candidate, $font, $size) > $width) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        return $line === '' ? $lines : [...$lines, $line];
    }

    private function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text) ?: [0, 0, 0, 0, 0, 0, 0, 0];

        return (int) abs($box[2] - $box[0]);
    }

    private function text(GdImage $img, string $text, int $size, string $font, int $x, int $y, string $hex, bool $center = false, bool $right = false, int $tracking = 0): void
    {
        $color = $this->color($img, $hex);
        if ($tracking > 0) {
            foreach (mb_str_split($text) as $char) {
                imagettftext($img, $size, 0, $x, $y, $color, $font, $char);
                $x += $this->width($char, $font, $size) + $tracking;
            }

            return;
        }
        $w = $this->width($text, $font, $size);
        $x = $center ? $x - intdiv($w, 2) : ($right ? $x - $w : $x);
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
    }

    private function glow(GdImage $img, int $cx, int $cy, int $radius, string $hex, float $strength): void
    {
        for ($r = $radius; $r > 0; $r -= 12) {
            $alpha = $strength * (1 - $r / $radius) / 6;
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $this->color($img, $hex, $alpha));
        }
    }

    // GD colour from hex; $opacity 0..1 (GD alpha is 0 = opaque … 127 = transparent).
    private function color(GdImage $img, string $hex, float $opacity = 1.0): int
    {
        [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));

        return (int) imagecolorallocatealpha($img, $r, $g, $b, (int) round(127 * (1 - max(0, min(1, $opacity)))));
    }
}
