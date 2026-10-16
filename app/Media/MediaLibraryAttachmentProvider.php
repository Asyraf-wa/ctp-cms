<?php

namespace App\Media;

use App\Models\Media;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\Contracts\FileAttachmentProvider;
use Filament\Forms\Components\RichEditor\RichContentAttribute;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

// PRD EDIT-04: images dropped, pasted or attached in the editor go into the media library (deduped, EXIF stripped).
// The editor stores the media id; the render pipeline turns it into a responsive <img>.
class MediaLibraryAttachmentProvider implements FileAttachmentProvider
{
    public function attribute(RichContentAttribute $attribute): static
    {
        return $this;
    }

    public function getFileAttachmentUrl(mixed $file): ?string
    {
        return Media::find($file)?->url('1280');
    }

    public function saveUploadedFileAttachment(TemporaryUploadedFile $file): mixed
    {
        return app(MediaUploader::class)->store($file)->id;
    }

    public function getDefaultFileAttachmentVisibility(): ?string
    {
        return 'public';
    }

    public function isExistingRecordRequiredToSaveNewFileAttachments(): bool
    {
        return false;
    }

    // Files stay in the library when removed from an article; the library shows them as unused.
    public function cleanUpFileAttachments(array $exceptIds): void {}
}
