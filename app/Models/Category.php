<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Contracts\HasPublicPath;
use App\Support\Sidebar;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// PRD TAX-01: two levels (a category may have a parent, a child may not have children).
#[Fillable(['parent_id', 'name', 'slug', 'description', 'icon', 'technology_id', 'sort', 'seo_title', 'seo_description', 'legacy_id'])]
class Category extends Model implements HasPublicPath
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug;

    protected static function booted(): void
    {
        static::saved(fn () => Sidebar::flush());
        static::deleted(fn () => Sidebar::flush());
    }

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

    /** @return BelongsTo<Technology, $this> logo used for the category (and its tutorials without a tech stack) */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    /** @return HasMany<Article, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /** @return list<int> this category and its children: a parent's archive lists both */
    public function familyIds(): array
    {
        return [$this->id, ...$this->children()->pluck('id')->all()];
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
        // A plain loop: Collection::flatMap() would renumber the ids.
        $options = [];
        foreach (static::query()->roots()->ordered()->with('children')->get() as $root) {
            $options[$root->id] = $root->name;
            foreach ($root->children as $child) {
                $options[$child->id] = '— '.$child->name;
            }
        }
        unset($options[$exceptId]);

        return $options;
    }
}
