<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Route;

/**
 * @property-read Project $record
 */
class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The public project page lands in T2.10.
            Action::make('view')->label('View live')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => url($this->record->publicPath() ?? '/'), shouldOpenInNewTab: true)
                ->visible(fn () => Route::has('projects.show') && $this->record->isPubliclyVisible()),
            DeleteAction::make(),
        ];
    }
}
