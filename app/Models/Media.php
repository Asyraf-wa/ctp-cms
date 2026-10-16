<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * PRD MED: one uploaded file plus its generated variants.
 *
 * @property array<string, string>|null $variants width (or "og", "320-avif") => path on the disk
 */
#[Fillable(['folder_id', 'disk', 'path', 'filename', 'name', 'mime', 'extension', 'size', 'width', 'height', 'alt', 'caption', 'credit', 'variants', 'hash'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    // Raster formats we resize and convert to WebP variants.
    public const RASTER = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    protected static function booted(): void
    {
        static::deleted(function (Media $media) {
            Storage::disk($media->disk)->delete([$media->path, ...array_values($media->variants ?? [])]);
        });
    }

    protected function casts(): array
    {
        return ['variants' => 'array'];
    }

    /** @return BelongsTo<MediaFolder, $this> */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /** @return HasMany<MediaUsage, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    /** @param Builder<Media> $query */
    public function scopeImages(Builder $query): void
    {
        $query->where('mime', 'like', 'image/%');
    }

    /** @param Builder<Media> $query */
    public function scopeUnused(Builder $query): void
    {
        $query->whereDoesntHave('usages');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function isRaster(): bool
    {
        return in_array($this->mime, self::RASTER, true);
    }

    public function isInUse(): bool
    {
        return $this->usages()->exists();
    }

    public function url(?string $variant = null): string
    {
        $path = $variant !== null ? ($this->variants[$variant] ?? $this->path) : $this->path;

        return Storage::disk($this->disk)->url($path);
    }

    // Small preview for grids and pickers; the original until the variant exists.
    public function thumbUrl(): string
    {
        return $this->url('320');
    }

    // "320w, 768w, 1280w" plus the original at its own width.
    public function srcset(): ?string
    {
        if (! $this->isRaster()) {
            return null;
        }

        /** @var list<int> $widths */
        $widths = config('ctp.media.widths');
        $variants = $this->variants ?? [];

        $sources = collect($widths)
            ->filter(fn (int $w) => array_key_exists((string) $w, $variants))
            ->map(fn (int $w) => $this->url((string) $w)." {$w}w");

        if ($this->width) {
            $sources->push($this->url()." {$this->width}w");
        }

        return $sources->implode(', ') ?: null;
    }

    public function displayName(): string
    {
        return $this->name ?: $this->filename;
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    public function dimensions(): ?string
    {
        return $this->width && $this->height ? "{$this->width} × {$this->height}" : null;
    }
}
