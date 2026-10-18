<?php

namespace App\Filament\Support;

use Filament\Forms\Components\RichEditor;

// EDIT-01..04: the one editor used for article, page and project bodies (blocks + media library).
class ContentEditor
{
    public static function make(string $name = 'body', string $minHeight = '32rem'): RichEditor
    {
        return RichEditor::make($name)->label('Content')
            ->toolbarButtons([
                ['bold', 'italic', 'strike', 'code', 'link'],
                ['h2', 'h3', 'h4'],
                ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ['codeBlock', 'table', 'attachFiles', 'customBlocks', 'details'],
                ['undo', 'redo'],
            ])
            ->fileAttachmentsAcceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'])
            ->fileAttachmentsMaxSize((int) config('ctp.media.max_upload_kb'))
            ->customBlocksGrid()
            ->searchableCustomBlocks()
            ->stickyToolbar()
            ->extraInputAttributes(['style' => "min-height: {$minHeight}"])
            ->helperText('Use “Insert block” for code, images from the library, callouts, steps, terminal output and videos. Pasted or attached images go into the media library.');
    }
}
