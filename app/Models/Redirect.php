<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

// PRD SEO-11 / SLUG-03 / MIG-05.
#[Fillable(['from_path', 'to_path', 'status_code', 'source'])]
class Redirect extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_LEGACY = 'legacy';

    public const SOURCE_SLUG = 'slug';

    protected function casts(): array
    {
        return ['last_hit_at' => 'datetime'];
    }

    public static function normalizePath(string $path): string
    {
        $path = strtolower(parse_url($path, PHP_URL_PATH) ?: '/');

        return '/'.trim($path, '/');
    }

    /**
     * Point $from at $to, keeping the table free of chains and loops:
     * anything that pointed at $from now points straight at $to, and a redirect away from $to is removed
     * (it would make the live page unreachable).
     */
    public static function record(string $from, ?string $to, string $source = self::SOURCE_MANUAL, int $status = 301): ?self
    {
        $from = self::normalizePath($from);
        // External targets (https://…) are kept as given; internal ones are normalised like $from.
        $to = $to === null ? null : (preg_match('#^https?://#i', $to) && ! str_starts_with(strtolower($to), strtolower(url('/'))) ? $to : self::normalizePath($to));

        if ($from === $to) {
            return null;
        }

        if ($to !== null && str_starts_with($to, '/')) {
            static::where('from_path', $to)->delete();
            static::where('to_path', $from)->update(['to_path' => $to]);
        }

        return static::updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => $to === null ? 410 : $status, 'source' => $source],
        );
    }

    public static function findForPath(string $path): ?self
    {
        return static::where('from_path', self::normalizePath($path))->first();
    }

    public function toResponse(): RedirectResponse|Response
    {
        $this->increment('hits', 1, ['last_hit_at' => now()]);

        return $this->to_path === null
            ? response('Gone', 410)
            : redirect($this->to_path, $this->status_code);
    }
}
