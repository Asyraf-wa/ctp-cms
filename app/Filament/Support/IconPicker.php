<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;

// A searchable Lucide icon select with previews (categories, menu items).
class IconPicker
{
    // Curated for a web-development tutorial site; any Lucide name works in menus too.
    public const ICONS = [
        'code-xml', 'braces', 'brackets', 'terminal', 'square-terminal', 'file-code', 'binary', 'bug', 'cpu',
        'server', 'database', 'cloud', 'globe', 'network', 'shield-check', 'lock', 'key-round',
        'layout-template', 'layout-dashboard', 'palette', 'paintbrush', 'pen-tool', 'figma', 'image', 'monitor-smartphone',
        'wrench', 'hammer', 'settings', 'puzzle', 'package', 'boxes', 'container', 'git-branch', 'github',
        'book-open', 'graduation-cap', 'lightbulb', 'notebook-pen', 'newspaper', 'rocket', 'zap', 'sparkles',
        'chart-line', 'calculator', 'mail', 'message-square', 'users', 'smartphone', 'gamepad-2', 'folder', 'tag',
    ];

    public static function make(string $name = 'icon'): Select
    {
        return Select::make($name)
            ->label('Icon')
            ->searchable()
            ->allowHtml()
            ->options(collect(self::ICONS)->mapWithKeys(fn (string $icon) => [
                $icon => '<span style="display:inline-flex;align-items:center;gap:.5rem">'
                    .svg('lucide-'.$icon, '', ['style' => 'width:1.1rem;height:1.1rem'])->toHtml()
                    .e($icon).'</span>',
            ])->all());
    }
}
