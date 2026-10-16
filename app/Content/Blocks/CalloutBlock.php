<?php

namespace App\Content\Blocks;

use App\Content\Markdown;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;

// PRD EDIT-05: note / tip / warning / danger box (DESIGN §2 callout colours, always with an icon and label).
class CalloutBlock extends RichContentCustomBlock
{
    public const TYPES = [
        'note' => ['Note', 'info'],
        'tip' => ['Tip', 'lightbulb'],
        'warning' => ['Warning', 'triangle-alert'],
        'danger' => ['Danger', 'octagon-alert'],
    ];

    public static function getId(): string
    {
        return 'callout';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            ToggleButtons::make('type')->inline()->required()->default('note')
                ->options(array_map(fn (array $t) => $t[0], self::TYPES))
                ->colors(['note' => 'info', 'tip' => 'success', 'warning' => 'warning', 'danger' => 'danger']),
            TextInput::make('title')->placeholder('Defaults to the type, e.g. “Tip”')->maxLength(120),
            Textarea::make('body')->required()->rows(5)
                ->helperText('Markdown: **bold**, *italic*, `code`, [links](https://…) and lists.'),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'Callout · '.(self::TYPES[$config['type'] ?? 'note'][0] ?? 'Note');
    }

    public static function toPreviewHtml(array $config): string
    {
        return '<strong>'.e($config['title'] ?? '' ?: self::TYPES[$config['type'] ?? 'note'][0]).'</strong>'.Markdown::block($config['body'] ?? '');
    }

    public static function shouldApplyProseStylingToPreview(array $config): bool
    {
        return true;
    }

    public static function toHtml(array $config, array $data): string
    {
        $type = array_key_exists($config['type'] ?? '', self::TYPES) ? $config['type'] : 'note';
        [$label, $icon] = self::TYPES[$type];
        $title = e(filled($config['title'] ?? null) ? $config['title'] : $label);
        $svg = svg('lucide-'.$icon, 'callout__icon', ['aria-hidden' => 'true'])->toHtml();
        $body = Markdown::block($config['body'] ?? '');

        return <<<HTML
            <aside class="callout callout--{$type}" role="note" aria-label="{$label}">
            {$svg}<div class="callout__content"><p class="callout__title">{$title}</p>{$body}</div>
            </aside>
            HTML;
    }
}
