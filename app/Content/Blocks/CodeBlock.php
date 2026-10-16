<?php

namespace App\Content\Blocks;

use App\Content\CodeHighlighter;
use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

// PRD EDIT-03: a code block edited in a real code editor.
class CodeBlock extends RichContentCustomBlock
{
    public static function getIcon(): Heroicon
    {
        return Heroicon::OutlinedCodeBracket;
    }

    public static function getId(): string
    {
        return 'code';
    }

    public static function getLabel(): string
    {
        return 'Code';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('5xl')
            ->modalDescription('Paste or type code. It is highlighted on the site.')
            ->schema([
                Grid::make(4)->schema([
                    Select::make('language')->options(CodeHighlighter::options())->default('php')->required()->live()->searchable(),
                    TextInput::make('filename')->placeholder('src/Controller/UsersController.php')->columnSpan(2),
                    TextInput::make('highlight')->label('Highlight lines')->placeholder('3, 5-7')
                        ->rule('regex:/^[\d,\-\s]*$/'),
                ]),
                CodeEditor::make('code')->hiddenLabel()->required()
                    ->language(fn (Get $get) => CodeHighlighter::editorLanguage($get('language'))),
                Toggle::make('line_numbers')->label('Show line numbers'),
            ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return collect(['Code', $config['filename'] ?? null ?: CodeHighlighter::options()[CodeHighlighter::normalize($config['language'] ?? null)]])->filter()->implode(' · ');
    }

    public static function toPreviewHtml(array $config): string
    {
        $lines = array_slice(explode("\n", (string) ($config['code'] ?? '')), 0, 12);

        return '<pre style="margin:0;max-height:16rem;overflow:hidden;font-size:12px;line-height:1.5;white-space:pre">'.e(implode("\n", $lines)).'</pre>';
    }

    public static function toHtml(array $config, array $data): string
    {
        return app(CodeHighlighter::class)->render(
            (string) ($config['code'] ?? ''),
            $config['language'] ?? null,
            $config['filename'] ?? null,
            $config['highlight'] ?? null,
            (bool) ($config['line_numbers'] ?? false),
        );
    }
}
