<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Media\MediaUploader;
use App\Models\MediaFolder;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    // 'grid' | 'list', remembered for the session.
    public string $mediaView = 'grid';

    // MED-01 browsing: null is the top level (its folders and the unfiled files), 'all' is every file,
    // a number is that folder. In the URL, so the browser's back button and a reload keep the place.
    #[Url(as: 'folder', except: null)]
    public ?string $folder = null;

    public function mount(): void
    {
        parent::mount();
        $this->mediaView = session('media.layout', 'grid');
    }

    public function openFolder(?string $folder = null): void
    {
        $this->folder = filled($folder) ? $folder : null;
        $this->deselectAllTableRecords();
        $this->resetPage();
    }

    public function currentFolder(): ?MediaFolder
    {
        return ctype_digit((string) $this->folder) ? MediaFolder::find((int) $this->folder) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('layout')
                ->label(fn () => $this->mediaView === 'grid' ? 'List view' : 'Grid view')
                ->icon(fn () => $this->mediaView === 'grid' ? Heroicon::OutlinedListBullet : Heroicon::OutlinedSquares2x2)
                ->color('gray')
                ->action(function () {
                    $this->mediaView = $this->mediaView === 'grid' ? 'list' : 'grid';
                    session(['media.layout' => $this->mediaView]);
                    // The table was built before this action ran; rebuild it so this response uses the new layout.
                    $this->table = $this->table($this->makeTable());
                }),
            Action::make('newFolder')
                ->label('New folder')
                ->icon(Heroicon::OutlinedFolderPlus)
                ->color('gray')
                ->schema([
                    TextInput::make('name')->required()->maxLength(100),
                    Select::make('parent_id')->label('Inside')->placeholder('Top level')->options(fn () => MediaFolder::options())
                        ->default(fn () => $this->currentFolder()?->id),
                ])
                ->action(function (array $data) {
                    MediaFolder::create($data);
                    Notification::make()->success()->title("Folder “{$data['name']}” created")->send();
                }),
            self::uploadAction(),
        ];
    }

    public static function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Upload')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalWidth('2xl')
            ->schema([
                Select::make('folder_id')->label('Folder')->placeholder('Unfiled')->options(fn () => MediaFolder::options())
                    // Upload into the folder being browsed.
                    ->default(fn ($livewire) => $livewire instanceof self ? $livewire->currentFolder()?->id : null),
                self::fileInput('files')->multiple()->maxParallelUploads(3)->required(),
            ])
            ->action(function (array $data, MediaUploader $uploader) {
                $saved = 0;
                $errors = [];

                foreach ($data['files'] as $file) {
                    try {
                        $uploader->store($file, $data['folder_id'] ?? null);
                        $saved++;
                    } catch (ValidationException $e) {
                        $errors[] = $e->validator->errors()->first();
                    }
                }

                if ($saved) {
                    Notification::make()->success()->title($saved === 1 ? '1 file uploaded' : "{$saved} files uploaded")->send();
                }
                if ($errors) {
                    Notification::make()->danger()->title('Some files were not uploaded')->body(implode('<br>', array_map('e', $errors)))->persistent()->send();
                }
            });
    }

    // Files stay as temporary uploads; MediaUploader validates and stores them itself.
    public static function fileInput(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->hiddenLabel()
            ->storeFiles(false)
            ->acceptedFileTypes(array_keys(config('ctp.media.mimes')))
            ->maxSize((int) config('ctp.media.max_upload_kb'))
            ->helperText('Images (JPG, PNG, GIF, WebP, AVIF, SVG), PDF or ZIP, up to '.round(config('ctp.media.max_upload_kb') / 1024).' MB each. Location data is removed from photos.');
    }
}
