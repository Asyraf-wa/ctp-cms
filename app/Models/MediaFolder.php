<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// PRD MED-01: folders for organising the library (files in a deleted folder become unfiled).
#[Fillable(['parent_id', 'name'])]
class MediaFolder extends Model
{
    /** @return BelongsTo<MediaFolder, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    /** @return HasMany<MediaFolder, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id')->orderBy('name');
    }

    /** @return HasMany<Media, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    public function path(): string
    {
        return $this->parent ? $this->parent->path().' / '.$this->name : $this->name;
    }

    /** @return array<int, string> id => "Parent / Child", sorted by path */
    public static function options(): array
    {
        return static::with('parent.parent')->get()
            ->mapWithKeys(fn (MediaFolder $folder) => [$folder->id => $folder->path()])
            ->sort()
            ->all();
    }
}
