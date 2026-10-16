<?php

namespace App\Filament\Resources\Series;

use App\Filament\Forms\Components\MediaPicker;
use App\Filament\Resources\Series\Pages\ManageSeries;
use App\Filament\Support\SlugFields;
use App\Models\Series;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

// PRD TAX-03. The ordered list of tutorials is managed from each article (series + part number) in Phase 1D.
class SeriesResource extends Resource
{
    protected static ?string $model = Series::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 22;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'series';

    protected static ?string $pluralModelLabel = 'series';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                SlugFields::source('title', 'Title'),
                SlugFields::slug('/series'),
                Textarea::make('description')->rows(3)->columnSpanFull()
                    ->helperText('What the reader will be able to do after finishing the series.'),
                MediaPicker::make('cover_media_id')->label('Cover image')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->modifyQueryUsing(fn ($query) => $query->with('cover'))
            ->columns([
                ImageColumn::make('cover')->label('')->state(fn (Series $record) => $record->cover?->thumbUrl())->imageWidth(64)->imageHeight(40),
                TextColumn::make('title')->searchable()->description(fn (Series $record) => $record->description ? str($record->description)->limit(90) : null),
                TextColumn::make('slug')->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSeries::route('/'),
        ];
    }
}
