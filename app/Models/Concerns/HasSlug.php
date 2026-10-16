<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use Illuminate\Support\Str;

/**
 * PRD SLUG-01, SLUG-03: lowercase kebab-case slugs, unique within the model's scope,
 * and a 301 from the old URL whenever a public slug changes.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(fn (self $model) => $model->fillSlug());
        static::updated(fn (self $model) => $model->redirectOldSlug());
    }

    protected function slugSourceColumn(): string
    {
        return 'title';
    }

    // Public path for a slug, e.g. "/about". Null means the model has no public URL.
    abstract public function pathForSlug(string $slug): ?string;

    public function isPubliclyVisible(): bool
    {
        return true;
    }

    public static function normalizeSlug(?string $value): string
    {
        return Str::slug((string) $value);
    }

    protected function slugIsTaken(string $slug): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }

    protected function fillSlug(): void
    {
        $base = static::normalizeSlug($this->slug ?: $this->{$this->slugSourceColumn()});
        $base = $base !== '' ? $base : Str::lower(Str::random(8));

        $slug = $base;
        for ($i = 2; $this->slugIsTaken($slug); $i++) {
            $slug = "{$base}-{$i}";
        }

        $this->slug = $slug;
    }

    protected function redirectOldSlug(): void
    {
        $old = $this->getOriginal('slug');

        if (! $this->wasChanged('slug') || blank($old) || ! $this->isPubliclyVisible()) {
            return;
        }

        $from = $this->pathForSlug($old);
        $to = $this->pathForSlug($this->slug);

        if ($from !== null && $to !== null) {
            Redirect::record($from, $to, Redirect::SOURCE_SLUG);
        }
    }
}
