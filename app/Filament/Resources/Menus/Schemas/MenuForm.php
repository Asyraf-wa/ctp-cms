<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Models\Article;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Project;
use App\Models\Series;
use App\Models\Tag;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()->columns(2)->schema([
                    TextInput::make('location')
                        ->formatStateUsing(fn (?string $state): string => Menu::LOCATIONS[$state] ?? (string) $state)
                        ->disabled()->dehydrated(false),
                    TextInput::make('name')->label('Heading')->required()->maxLength(60)
                        ->helperText('Shown above footer columns.'),
                ]),
                Section::make('Items')->description('Drag to reorder. Items can have one level of children (shown as a dropdown in the header).')->schema([
                    self::itemsRepeater('rootItems', withChildren: true),
                ]),
            ]);
    }

    private static function itemsRepeater(string $relationship, bool $withChildren): Repeater
    {
        return Repeater::make($relationship)
            ->relationship($relationship)
            ->hiddenLabel()
            ->orderColumn('sort')
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->defaultItems(0)
            ->addActionLabel($withChildren ? 'Add item' : 'Add child item')
            ->itemLabel(fn (array $state): string => self::itemLabel($state))
            ->schema([
                ...self::itemFields(),
                ...($withChildren ? [self::itemsRepeater('children', withChildren: false)->label('Children')->hiddenLabel(false)] : []),
            ]);
    }

    /** @return array<int, Component> */
    private static function itemFields(): array
    {
        return [
            Grid::make(3)->schema([
                Select::make('type')->required()->live()->default('route')
                    ->options(['route' => 'Site section', 'url' => 'Custom link'] + MenuItem::CONTENT_TYPES)
                    ->afterStateUpdated(fn (Set $set) => $set('linkable_id', null)),
                Select::make('route')->label('Section')->required()->live()
                    ->options(collect(MenuItem::ROUTES)->map(fn (array $route) => $route[0]))
                    ->visible(fn (Get $get): bool => $get('type') === 'route')
                    ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => blank($get('label')) && $state ? $set('label', MenuItem::ROUTES[$state][0]) : null),
                TextInput::make('url')->label('URL')->required()
                    ->placeholder('/about or https://…')
                    ->rule('regex:#^(/|https?://|mailto:)#i')
                    ->visible(fn (Get $get): bool => $get('type') === 'url'),
                Select::make('linkable_id')->label('Content')->required()->searchable()->live()
                    ->options(fn (Get $get): array => self::contentOptions($get('type')))
                    ->visible(fn (Get $get): bool => array_key_exists((string) $get('type'), MenuItem::CONTENT_TYPES))
                    ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => blank($get('label')) && $state
                        ? $set('label', self::contentOptions($get('type'))[$state] ?? null) : null),
                TextInput::make('label')->required()->maxLength(60),
            ]),
            Grid::make(5)->schema([
                Toggle::make('is_enabled')->label('Visible')->default(true)->inline(false),
                Select::make('target')->label('Opens in')->default('_self')->required()
                    ->options(['_self' => 'Same tab', '_blank' => 'New tab']),
                TextInput::make('icon')->label('Icon')->placeholder('e.g. code-xml')
                    ->helperText('Lucide icon name.'),
                TextInput::make('badge')->maxLength(20)->placeholder('New'),
                TextInput::make('css_class')->label('CSS class')->maxLength(100),
            ]),
        ];
    }

    /** @return array<int|string, string> */
    private static function contentOptions(?string $type): array
    {
        return match ($type) {
            'article' => Article::orderBy('title')->pluck('title', 'id')->all(),
            'page' => Page::orderBy('title')->pluck('title', 'id')->all(),
            'category' => Category::treeOptions(),
            'tag' => Tag::orderBy('name')->pluck('name', 'id')->all(),
            'series' => Series::orderBy('title')->pluck('title', 'id')->all(),
            'project' => Project::orderBy('title')->pluck('title', 'id')->all(),
            default => [],
        };
    }

    /** @param array<string, mixed> $state */
    private static function itemLabel(array $state): string
    {
        $label = $state['label'] ?: 'New item';

        $item = (new MenuItem)->forceFill([
            'type' => $state['type'] ?? null,
            'linkable_type' => $state['type'] ?? null,
            'linkable_id' => $state['linkable_id'] ?? null,
        ]);

        return match (true) {
            filled($state['linkable_id'] ?? null) && $item->isBroken() => "⚠ {$label} (content missing or unpublished)",
            ! ($state['is_enabled'] ?? true) => "{$label} (hidden)",
            default => $label,
        };
    }
}
