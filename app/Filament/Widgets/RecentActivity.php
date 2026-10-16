<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

// ADM-01, CONTACT-06: the latest contact messages (spam left out).
class RecentActivity extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Latest messages';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ContactMessage::query()->where('status', '!=', ContactMessage::SPAM))
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No messages yet')
            ->emptyStateIcon('heroicon-o-inbox')
            ->recordUrl(fn (ContactMessage $record) => ContactMessageResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->weight(fn (ContactMessage $record) => $record->status === ContactMessage::NEW ? 'bold' : 'normal')
                    ->description(fn (ContactMessage $record) => $record->topicLabel()),
                TextColumn::make('created_at')->label('Received')->since(),
            ]);
    }
}
