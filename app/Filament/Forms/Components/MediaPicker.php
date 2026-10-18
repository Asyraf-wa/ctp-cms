<?php

namespace App\Filament\Forms\Components;

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Media\MediaUploader;
use App\Models\Media;
use App\Models\MediaFolder;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * PRD MED-06: pick (or upload) a library item. The state is the media id.
 * Used for featured images, series covers, project covers and OG images.
 */
class MediaPicker extends Field
{
    protected string $view = 'filament.forms.components.media-picker';

    protected bool $imagesOnly = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerActions([
            fn (MediaPicker $component): Action => $component->getChooseAction(),
            fn (MediaPicker $component): Action => $component->getRemoveAction(),
        ]);
    }

    public function imagesOnly(bool $condition = true): static
    {
        $this->imagesOnly = $condition;

        return $this;
    }

    public function getMedia(): ?Media
    {
        $id = $this->getState();

        return $id ? Media::find($id) : null;
    }

    public function getChooseAction(): Action
    {
        return Action::make('chooseMedia')
            ->label(fn (MediaPicker $component) => $component->getState() ? 'Change' : 'Choose from library')
            ->icon(Heroicon::OutlinedPhoto)
            ->color('gray')
            ->modalHeading('Media library')
            ->modalWidth('6xl')
            ->modalSubmitActionLabel('Use selected')
            ->disabled(fn (MediaPicker $component) => $component->isDisabled())
            ->fillForm(fn (MediaPicker $component) => ['selected' => $component->getState()])
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('search')->hiddenLabel()->placeholder('Search name, file or alt text')
                        ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)->live(debounce: 400)->columnSpan(2),
                    Select::make('folder')->hiddenLabel()->placeholder('All folders')
                        ->options(fn () => ['none' => 'Unfiled'] + MediaFolder::options())->live(),
                ]),
                MediaGrid::make('selected')->hiddenLabel()
                    ->query(fn (Builder $query, Get $get) => $query
                        ->when($this->imagesOnly, fn (Builder $q) => $q->where('mime', 'like', 'image/%'))
                        ->when(filled($get('search')), fn (Builder $q) => $q->where(fn (Builder $q) => $q
                            ->where('name', 'like', '%'.$get('search').'%')
                            ->orWhere('filename', 'like', '%'.$get('search').'%')
                            ->orWhere('alt', 'like', '%'.$get('search').'%')))
                        ->when($get('folder') === 'none', fn (Builder $q) => $q->whereNull('folder_id'))
                        ->when(filled($get('folder')) && $get('folder') !== 'none', fn (Builder $q) => $q->where('folder_id', $get('folder')))),
                ListMedia::fileInput('upload')->label('Or upload a new file')->hiddenLabel(false)
                    ->when($this->imagesOnly, fn ($input) => $input->image()),
            ])
            ->action(function (MediaPicker $component, array $data, MediaUploader $uploader) {
                $id = $data['selected'] ?? null;

                if (filled($data['upload'] ?? null)) {
                    try {
                        $id = $uploader->store($data['upload'])->id;
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title($e->validator->errors()->first())->send();

                        throw new Halt;
                    }
                }

                if (blank($id)) {
                    Notification::make()->warning()->title('Choose an item or upload a file')->send();

                    throw new Halt;
                }

                $component->state((int) $id);
                $component->callAfterStateUpdated();
            });
    }

    public function getRemoveAction(): Action
    {
        return Action::make('removeMedia')
            ->label('Remove')
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->link()
            ->visible(fn (MediaPicker $component) => filled($component->getState()) && ! $component->isDisabled())
            ->action(function (MediaPicker $component) {
                $component->state(null);
                $component->callAfterStateUpdated();
            });
    }
}
