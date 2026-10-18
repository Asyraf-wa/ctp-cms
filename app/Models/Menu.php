<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

// PRD MENU-01: one menu per location; the theme reads menus by location.
#[Fillable(['location', 'name'])]
class Menu extends Model
{
    public const LOCATIONS = [
        'header' => 'Header navigation',
        'footer_1' => 'Footer column 1',
        'footer_2' => 'Footer column 2',
        'footer_3' => 'Footer column 3',
        'legal' => 'Footer bottom bar',
    ];

    private const CACHE_KEY = 'menus.v1';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /** @return HasMany<MenuItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort');
    }

    /** @return HasMany<MenuItem, $this> */
    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }

    /**
     * Enabled items for a location, nested two levels, with links resolved. Cached until any menu changes (MENU-05).
     *
     * @return list<array{label: string, href: ?string, target: string, icon: ?string, badge: ?string, class: ?string, children: list<array<string, mixed>>}>
     */
    public static function tree(string $location): array
    {
        return self::cached()[$location]['items'] ?? [];
    }

    // The menu's heading (footer column title), from the same cache.
    public static function heading(string $location): ?string
    {
        return self::cached()[$location]['name'] ?? null;
    }

    /** @return array<string, array{name: string, items: list<array<string, mixed>>}> */
    private static function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()
            ->with(['rootItems' => fn ($q) => $q->where('is_enabled', true)->with([
                'linkable',
                'children' => fn ($q) => $q->where('is_enabled', true)->with('linkable'),
            ])])
            ->get()
            ->mapWithKeys(fn (Menu $menu) => [$menu->location => [
                'name' => $menu->name,
                'items' => self::present($menu->rootItems),
            ]])
            ->all());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @return list<array<string, mixed>>
     */
    private static function present(Collection $items): array
    {
        return $items
            ->map(fn (MenuItem $item) => [
                'label' => $item->label,
                'href' => $item->href(),
                'target' => $item->target,
                'icon' => $item->icon,
                'badge' => $item->badge,
                'class' => $item->css_class,
                'children' => $item->relationLoaded('children') ? self::present($item->children) : [],
            ])
            // Items whose content was deleted disappear from the site; the admin flags them (MENU-04).
            ->filter(fn (array $item) => $item['href'] !== null || $item['children'] !== [])
            ->values()
            ->all();
    }
}
