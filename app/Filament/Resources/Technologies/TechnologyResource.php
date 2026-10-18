<?php

namespace App\Filament\Resources\Technologies;

use App\Filament\Forms\Components\MediaPicker;
use App\Filament\Resources\Technologies\Pages\ManageTechnologies;
use App\Filament\Support\IconPicker;
use App\Filament\Support\SlugFields;
use App\Models\Technology;
use App\Support\SimpleIcons;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Html as HtmlBlock;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

// Technologies with logos: tutorial tech stacks, project stacks and category icons.
class TechnologyResource extends Resource
{
    protected static ?string $model = Technology::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 23;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Technologies';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(self::formSchema());
    }

    /** @return list<mixed> shared with the inline "create" form of TechnologyPicker */
    public static function formSchema(): array
    {
        return [
            ToggleButtons::make('icon_type')->label('Logo')->inline()->required()->default('simple')->live()
                ->options(Technology::TYPES)
                ->afterStateUpdated(fn (Set $set) => $set('icon', null))
                ->columnSpanFull(),
            Select::make('icon')->label(fn (Get $get) => $get('icon_type') === 'lucide' ? 'Icon' : 'Brand')
                ->visible(fn (Get $get) => $get('icon_type') === 'simple')
                ->required(fn (Get $get) => $get('icon_type') === 'simple')
                ->searchable()->allowHtml()
                ->placeholder('Search 3,400 brand logos, e.g. Laravel')
                ->getSearchResultsUsing(fn (string $search) => collect(SimpleIcons::search($search))
                    ->mapWithKeys(fn (string $title, string $slug) => [$slug => self::brandLabel($slug, $title)])->all())
                ->getOptionLabelUsing(fn (?string $value) => ($icon = SimpleIcons::find($value)) ? self::brandLabel($icon['slug'], $icon['title']) : $value)
                ->live()
                ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                    $icon = SimpleIcons::find($state);
                    if (! $icon) {
                        return;
                    }
                    $set('color', $icon['hex']);
                    if (blank($get('name'))) {
                        $set('name', $icon['title']);
                        $set('slug', Str::slug($icon['title']));
                    }
                })
                ->helperText('From Simple Icons (CC0). Picking one fills the name and brand colour.')
                ->columnSpanFull(),
            IconPicker::make('icon')
                ->visible(fn (Get $get) => $get('icon_type') === 'lucide')
                ->required(fn (Get $get) => $get('icon_type') === 'lucide')
                ->columnSpanFull(),
            MediaPicker::make('icon_media_id')->label('SVG logo')
                ->visible(fn (Get $get) => $get('icon_type') === 'media')
                ->required(fn (Get $get) => $get('icon_type') === 'media')
                ->helperText('For logos missing from Simple Icons (e.g. VS Code). Use an SVG with a transparent background.')
                ->columnSpanFull(),
            SlugFields::source('name', 'Name')->maxLength(60),
            SlugFields::slug('/tech')->maxLength(80),
            ColorPicker::make('color')->label('Brand colour')
                ->helperText('Logos are grey and take this colour on hover. Dark colours are lightened automatically in dark mode.'),
            TextInput::make('website_url')->label('Official website')->url(),
            HtmlBlock::make(fn (Get $get) => self::preview($get))->columnSpanFull(),
        ];
    }

    private static function brandLabel(string $slug, string $title): string
    {
        return '<span style="display:inline-flex;align-items:center;gap:.5rem">'
            .svg('si-'.$slug, '', ['style' => 'width:1.1rem;height:1.1rem', 'fill' => 'currentColor'])->toHtml()
            .e($title).'</span>';
    }

    // Live preview of the grey and hover states.
    private static function preview(Get $get): string
    {
        $technology = new Technology([
            'name' => $get('name'), 'icon_type' => $get('icon_type'), 'icon' => $get('icon'),
            'icon_media_id' => $get('icon_media_id'), 'color' => $get('color'),
        ]);
        if (blank($get('icon')) && blank($get('icon_media_id'))) {
            return '';
        }
        $icon = $technology->iconHtml('');
        $chip = fn (string $color, string $label) => '<span style="display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .75rem;border:1px solid rgb(148 163 184 / .4);border-radius:8px;font-size:.875rem">'
            .'<span style="display:inline-flex;width:1.25rem;height:1.25rem;color:'.e($color).'">'.$icon.'</span>'.e($label).'</span>';

        return '<div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center"><span style="font-size:.8rem;opacity:.7">Preview</span>'
            .$chip('#64748b', 'In lists').$chip($technology->brandColor() ?? '#0f766e', 'On hover')
            .($technology->darkColor() ? '<span style="display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .75rem;border-radius:8px;background:#111a2e;color:#e2e8f0;font-size:.875rem"><span style="display:inline-flex;width:1.25rem;height:1.25rem;color:'.e($technology->darkColor()).'">'.$icon.'</span>Dark mode</span>' : '')
            .'</div>';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->with('iconMedia')->withCount(['articles', 'projects', 'categories']))
            ->columns([
                ViewColumn::make('icon')->label('')->width('1%')->view('filament.columns.technology-icon'),
                TextColumn::make('name')->searchable()->sortable()
                    ->description(fn (Technology $record) => Technology::TYPES[$record->icon_type] ?? null),
                TextColumn::make('slug')->color('gray')->toggleable(),
                TextColumn::make('articles_count')->label('Tutorials')->sortable()->badge()->color('gray'),
                TextColumn::make('projects_count')->label('Projects')->sortable()->badge()->color('gray'),
                TextColumn::make('categories_count')->label('Categories')->badge()->color('gray')->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalDescription('Tutorials and projects lose this technology; categories fall back to their icon.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTechnologies::route('/'),
        ];
    }
}
