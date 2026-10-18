<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property-read ContactMessage $record
 */
class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->record->markRead();
    }

    public function getTitle(): string
    {
        return $this->record->topicLabel().' from '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ContactMessageResource::replyAction(),
            ContactMessageResource::archiveAction(),
            ContactMessageResource::spamAction(),
            DeleteAction::make(),
        ];
    }
}
