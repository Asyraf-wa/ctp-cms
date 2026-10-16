<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Support\IconPicker;
use App\Filament\Support\SlugFields;
use App\Models\Category;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

// PRD TAX-01.
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                SlugFields::source('name', 'Name'),
                SlugFields::slug('/category'),
                Select::make('parent_id')
                    ->label('Parent category')
                    ->placeholder('None (top level)')
                    ->options(fn (?Category $record) => Category::query()->roots()->ordered()
                        ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                        ->pluck('name', 'id'))
                    // Two levels only: a category with children stays top level.
                    ->rule(fn (?Category $record) => function (string $attribute, mixed $value, Closure $fail) use ($record) {
                        if ($value && $record?->children()->exists()) {
                            $fail('This category has sub-categories, so it must stay at the top level.');
                        }
                    }),
                IconPicker::make(),
                Textarea::make('description')->rows(2)->maxLength(500)->columnSpanFull()
                    ->helperText('Shown on the category page and in the sidebar tooltip.'),
                Section::make('Search engines')->collapsed()->columnSpanFull()->columns(2)->schema([
                    TextInput::make('seo_title')->label('SEO title')->maxLength(70)
                        ->placeholder('Defaults to the name'),
                    Textarea::make('seo_description')->label('SEO description')->rows(2)->maxLength(320)
                        ->placeholder('Defaults to the description'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ViewColumn::make('icon')->view('filament.columns.lucide-icon')->label('')->width('1%'),
                TextColumn::make('name')
                    ->formatStateUsing(fn (Category $record) => $record->fullName())
                    ->description(fn (Category $record) => $record->description)
                    ->searchable(),
                TextColumn::make('slug')->color('gray')->toggleable(),
                TextColumn::make('children_count')->counts('children')->label('Sub-categories')->badge()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('parent_id')->label('Parent')
                    ->options(fn () => Category::query()->roots()->ordered()->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
