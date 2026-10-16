<?php

namespace App\Content\Blocks;

use App\Content\CodeHighlighter;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

// PRD EDIT-05: the same step in several languages or operating systems.
class CodeTabsBlock extends RichContentCustomBlock
{
    public static function getIcon(): Heroicon
    {
        return Heroicon::OutlinedRectangleGroup;
    }

    public static function getId(): string
    {
        return 'code_tabs';
    }

    public static function getLabel(): string
    {
        return 'Code tabs';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->modalWidth('5xl')->schema([
            Repeater::make('tabs')->minItems(2)->defaultItems(2)->reorderable()->collapsible()
                ->itemLabel(fn (array $state) => $state['label'] ?? null)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('label')->required()->placeholder('Windows / macOS / npm…')->maxLength(40),
                        Select::make('language')->options(CodeHighlighter::options())->default('bash')->required()->searchable(),
                        TextInput::make('filename'),
                    ]),
                    Textarea::make('code')->required()->rows(6)->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace']),
                ]),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        /** @var list<array{label?: string}> $tabs */
        $tabs = $config['tabs'] ?? [];

        return 'Code tabs · '.collect($tabs)->pluck('label')->filter()->implode(' / ');
    }

    public static function toHtml(array $config, array $data): string
    {
        $tabs = array_values($config['tabs'] ?? []);
        $highlighter = app(CodeHighlighter::class);
        $id = 'tabs-'.substr(md5(json_encode($tabs)), 0, 8);

        $buttons = '';
        $panels = '';
        foreach ($tabs as $i => $tab) {
            $label = e($tab['label'] ?? 'Tab '.($i + 1));
            $buttons .= "<button type=\"button\" role=\"tab\" id=\"{$id}-t{$i}\" aria-controls=\"{$id}-p{$i}\" :aria-selected=\"active === {$i}\" @click=\"active = {$i}\" class=\"code-tabs__tab\">{$label}</button>";
            $code = $highlighter->render((string) ($tab['code'] ?? ''), $tab['language'] ?? null, $tab['filename'] ?? null);
            $panels .= "<div role=\"tabpanel\" id=\"{$id}-p{$i}\" aria-labelledby=\"{$id}-t{$i}\" x-show=\"active === {$i}\" class=\"code-tabs__panel\"><p class=\"code-tabs__label\">{$label}</p>{$code}</div>";
        }

        // Without JS every panel shows, each with its label; Alpine turns them into tabs.
        return "<div class=\"code-tabs\" x-data=\"{ active: 0 }\" :class=\"'is-tabbed'\"><div class=\"code-tabs__list\" role=\"tablist\">{$buttons}</div>{$panels}</div>";
    }
}
