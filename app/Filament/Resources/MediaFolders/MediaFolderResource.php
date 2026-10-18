<?php

namespace App\Filament\Resources\MediaFolders;

use App\Filament\Resources\MediaFolders\Pages\ManageMediaFolders;
use App\Models\MediaFolder;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

// PRD MED-01. Deleting a folder leaves its files in the library as "Unfiled".
class MediaFolderResource extends Resource
{
    protected static ?string $model = MediaFolder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Media';

    protected static ?string $navigationLabel = 'Folders';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'folder';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            Select::make('parent_id')->label('Inside')->placeholder('Top level')
                ->options(fn (?MediaFolder $record) => collect(MediaFolder::options())->except($record?->getKey())->all()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->state(fn (MediaFolder $record) => $record->path())->searchable(),
                TextColumn::make('media_count')->counts('media')->label('Files')->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalDescription('Files in this folder stay in the library as “Unfiled”.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMediaFolders::route('/'),
        ];
    }
}
