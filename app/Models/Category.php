<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Contracts\HasPublicPath;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// PRD TAX-01: two levels (a category may have a parent, a child may not have children).
#[Fillable(['parent_id', 'name', 'slug', 'description', 'icon', 'sort', 'seo_title', 'seo_description', 'legacy_id'])]
class Category extends Model implements HasPublicPath
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug;

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    public function pathForSlug(string $slug): ?string
    {
        return '/category/'.$slug;
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    /** @param Builder<Category> $query */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /** @param Builder<Category> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('name');
    }

    // "Parent › Child" for admin lists and breadcrumbs.
    public function fullName(): string
    {
        return $this->parent ? $this->parent->name.' › '.$this->name : $this->name;
    }

    /** @return array<int, string> id => name, parents followed by their children, for select options */
    public static function treeOptions(?int $exceptId = null): array
    {
        return static::query()->roots()->ordered()->with('children')->get()
            ->flatMap(fn (Category $root) => collect([$root->id => $root->name])
                ->union($root->children->mapWithKeys(fn (Category $child) => [$child->id => '— '.$child->name])))
            ->except($exceptId === null ? [] : [$exceptId])
            ->all();
    }
}
