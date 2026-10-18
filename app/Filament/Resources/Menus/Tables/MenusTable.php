<?php

namespace App\Filament\Resources\Menus\Tables;

use App\Models\Menu;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location')
                    ->formatStateUsing(fn (string $state): string => Menu::LOCATIONS[$state] ?? $state)
                    ->description(fn (Menu $record): string => $record->location),
                TextColumn::make('name')->label('Heading'),
                TextColumn::make('items_count')->counts('items')->label('Items')->badge(),
            ])
            ->defaultSort('id')
            ->paginated(false)
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
