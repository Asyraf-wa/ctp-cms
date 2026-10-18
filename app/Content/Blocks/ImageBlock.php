<?php

namespace App\Content\Blocks;

use App\Filament\Forms\Components\MediaPicker;
use App\Models\Media;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

// PRD EDIT-04 / MED-04: an image from the library, with alt text required.
class ImageBlock extends RichContentCustomBlock
{
    public static function getIcon(): Heroicon
    {
        return Heroicon::OutlinedPhoto;
    }

    public static function getId(): string
    {
        return 'image';
    }

    public static function getLabel(): string
    {
        return 'Image from library';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->modalWidth('3xl')->schema([
            MediaPicker::make('media_id')->label('Image')->required()->live(),
            TextInput::make('alt')->label('Alt text')->maxLength(300)
                ->placeholder(fn (Get $get) => Media::find($get('media_id'))?->alt ?: 'Describe the image')
                ->required(fn (Get $get) => blank(Media::find($get('media_id'))?->alt))
                ->helperText('Leave empty to use the alt text saved in the library.'),
            TextInput::make('caption')->maxLength(500)
                ->placeholder(fn (Get $get) => Media::find($get('media_id'))?->caption),
            ToggleButtons::make('size')->inline()->default('normal')
                ->options(['normal' => 'Normal', 'wide' => 'Wide', 'small' => 'Small']),
            TextInput::make('link')->label('Link (optional)')->url(),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'Image · '.(Media::find($config['media_id'] ?? null)?->displayName() ?? 'missing');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $media = Media::find($config['media_id'] ?? null);

        return $media ? '<img src="'.e($media->thumbUrl()).'" alt="" style="max-height:180px;border-radius:8px">' : null;
    }

    // The final responsive markup is produced by ArticleRenderer, which resolves every image the same way.
    public static function toHtml(array $config, array $data): ?string
    {
        if (! filled($config['media_id'] ?? null)) {
            return null;
        }

        $attrs = [
            'data-media-id' => (int) $config['media_id'],
            'data-size' => $config['size'] ?? 'normal',
            'alt' => $config['alt'] ?? '',
            'data-caption' => $config['caption'] ?? '',
            'data-link' => $config['link'] ?? '',
        ];

        return '<img '.collect($attrs)->map(fn ($v, $k) => $k.'="'.e((string) $v).'"')->implode(' ').'>';
    }
}
