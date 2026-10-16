<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// D9: one row per tutorial/page living at domain/{slug}.
#[Fillable(['slug', 'sluggable_type', 'sluggable_id'])]
class RootSlug extends Model
{
    protected $primaryKey = 'slug';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /** @return MorphTo<Model, $this> */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }
}
