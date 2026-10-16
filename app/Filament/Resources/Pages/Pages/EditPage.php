<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\URL;

/**
 * @property-read Page $record
 */
class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('Preview')->icon(Heroicon::OutlinedEye)->color('gray')
                ->url(fn () => URL::temporarySignedRoute('pages.preview', now()->addDay(), ['page' => $this->record]), shouldOpenInNewTab: true),
            Action::make('view')->label('View live')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => url($this->record->publicPath() ?? '/'), shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->isPubliclyVisible()),
            DeleteAction::make(),
        ];
    }
}
