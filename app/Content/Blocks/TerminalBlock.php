<?php

namespace App\Content\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

// PRD EDIT-05: commands and their output, in a terminal window. Copy takes the commands only.
class TerminalBlock extends RichContentCustomBlock
{
    public static function getIcon(): Heroicon
    {
        return Heroicon::OutlinedCommandLine;
    }

    public static function getId(): string
    {
        return 'terminal';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->modalWidth('3xl')->schema([
            TextInput::make('title')->placeholder('Terminal')->maxLength(60),
            Textarea::make('commands')->required()->rows(4)->helperText('One command per line, without the $ prompt.')
                ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace']),
            Textarea::make('output')->rows(6)->helperText('Optional output shown after the commands.')
                ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace']),
        ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'Terminal'.(filled($config['title'] ?? null) ? ' · '.$config['title'] : '');
    }

    public static function toPreviewHtml(array $config): string
    {
        return '<pre style="margin:0;font-size:12px">'.e(collect(explode("\n", (string) ($config['commands'] ?? '')))->map(fn ($l) => '$ '.$l)->implode("\n")).'</pre>';
    }

    public static function toHtml(array $config, array $data): string
    {
        $title = e(filled($config['title'] ?? null) ? $config['title'] : 'Terminal');
        $commands = collect(preg_split('/\R/', trim((string) ($config['commands'] ?? ''))))
            ->filter(fn ($l) => $l !== '')
            ->map(fn (string $l) => '<span class="terminal__line"><span class="terminal__prompt" aria-hidden="true">$ </span>'.e($l).'</span>')
            ->implode(''); // the spans are display:block, so no newlines between them
        $output = filled($config['output'] ?? null) ? '<span class="terminal__output">'.e(rtrim($config['output'])).'</span>' : '';
        $copy = e(trim((string) ($config['commands'] ?? '')));

        return <<<HTML
            <figure class="terminal">
            <figcaption class="terminal__bar"><span class="terminal__dots" aria-hidden="true"><i></i><i></i><i></i></span><span class="terminal__title">{$title}</span><button type="button" class="code-block__copy" data-copy-text="{$copy}" aria-label="Copy commands">Copy</button></figcaption>
            <pre class="terminal__body"><code>{$commands}{$output}</code></pre>
            </figure>
            HTML;
    }
}
