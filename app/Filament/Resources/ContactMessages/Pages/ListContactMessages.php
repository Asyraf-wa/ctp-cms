<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getTabs(): array
    {
        // Filament injects closure arguments by name: it must be $query.
        return [
            'inbox' => Tab::make('Inbox')->badge(ContactMessage::where('status', ContactMessage::NEW)->count() ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [ContactMessage::NEW, ContactMessage::READ, ContactMessage::REPLIED])),
            'unread' => Tab::make('Unread')->modifyQueryUsing(fn (Builder $query) => $query->where('status', ContactMessage::NEW)),
            'archived' => Tab::make('Archived')->modifyQueryUsing(fn (Builder $query) => $query->where('status', ContactMessage::ARCHIVED)),
            'spam' => Tab::make('Spam')->modifyQueryUsing(fn (Builder $query) => $query->where('status', ContactMessage::SPAM)),
        ];
    }
}
