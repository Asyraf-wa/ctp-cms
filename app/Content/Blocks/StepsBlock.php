<?php

namespace App\Content\Blocks;

use App\Content\Markdown;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

// PRD EDIT-05: a numbered procedure. Also feeds the HowTo structured data (SEO-04).
class StepsBlock extends RichContentCustomBlock
{
    public static function getIcon(): Heroicon
    {
        return Heroicon::OutlinedNumberedList;
    }

    public static function getId(): string
    {
        return 'steps';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->modalWidth('4xl')->schema([
            Repeater::make('steps')->minItems(1)->defaultItems(2)->reorderable()->collapsible()
                ->itemLabel(fn (array $state) => $state['title'] ?? null)
                ->schema([
                    TextInput::make('title')->required()->maxLength(150),
                    Textarea::make('body')->rows(3)->helperText('Markdown supported.'),
                ]),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'Steps · '.count($config['steps'] ?? []);
    }

    public static function toPreviewHtml(array $config): string
    {
        /** @var list<array{title?: string, body?: string}> $steps */
        $steps = $config['steps'] ?? [];

        return '<ol>'.collect($steps)->map(fn ($s) => '<li>'.e($s['title'] ?? '').'</li>')->implode('').'</ol>';
    }

    public static function shouldApplyProseStylingToPreview(array $config): bool
    {
        return true;
    }

    public static function toHtml(array $config, array $data): string
    {
        /** @var list<array{title?: string, body?: string}> $steps */
        $steps = $config['steps'] ?? [];
        $items = collect($steps)->map(fn (array $step) => '<li class="steps__item"><p class="steps__title">'.e($step['title'] ?? '').'</p>'.Markdown::block($step['body'] ?? '').'</li>')->implode('');

        return "<ol class=\"steps\">{$items}</ol>";
    }
}
