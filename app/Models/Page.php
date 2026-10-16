<?php

namespace App\Models;

use App\Models\Concerns\HasRootSlug;
use App\Models\Contracts\HasPublicPath;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// PRD PUB-10: static pages at domain/{slug}.
#[Fillable(['title', 'slug', 'body', 'template', 'is_published'])]
class Page extends Model implements HasPublicPath
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasRootSlug;

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_published;
    }
}
