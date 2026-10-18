<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// PRD MED-05: "Used in" and delete protection.
#[Fillable(['media_id', 'usable_type', 'usable_id', 'field'])]
class MediaUsage extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    /** @return BelongsTo<Media, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /** @return MorphTo<Model, $this> */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Replace the usages a model records for each field.
     *
     * @param  array<string, list<int|string|null>>  $fields  field => media ids
     */
    public static function sync(Model $usable, array $fields): void
    {
        static::query()->where('usable_type', $usable->getMorphClass())->where('usable_id', $usable->getKey())->delete();

        $rows = collect($fields)
            ->flatMap(fn (array $ids, string $field) => collect($ids)->filter()->unique()
                ->map(fn ($id) => ['media_id' => (int) $id, 'usable_type' => $usable->getMorphClass(), 'usable_id' => $usable->getKey(), 'field' => $field]))
            ->values();

        // Ignore ids of media that no longer exist.
        $existing = Media::whereIn('id', $rows->pluck('media_id'))->pluck('id')->all();

        static::insert($rows->whereIn('media_id', $existing)->values()->all());
    }

    public static function forget(Model $usable): void
    {
        static::query()->where('usable_type', $usable->getMorphClass())->where('usable_id', $usable->getKey())->delete();
    }
}
