<?php

namespace App\Content\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

// PRD MIG-04 / EDIT-05: CodePen demo, loaded on click.
class CodePenBlock extends RichContentCustomBlock
{
    public static function getIcon(): Heroicon
    {
        return Heroicon::OutlinedCodeBracketSquare;
    }

    public static function getId(): string
    {
        return 'codepen';
    }

    public static function getLabel(): string
    {
        return 'CodePen demo';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('url')->label('CodePen URL')->required()->placeholder('https://codepen.io/user/pen/abcdef')
                ->rule('regex:~codepen\.io/[\w-]+/(?:pen|embed)/[\w-]+~'),
            TextInput::make('title')->required(),
            Select::make('tab')->label('Show first')->default('result')->options(['result' => 'Result', 'html' => 'HTML', 'css' => 'CSS', 'js' => 'JS', 'html,result' => 'HTML + Result']),
            TextInput::make('height')->numeric()->default(400)->minValue(200)->maxValue(1000),
        ]);
    }

    /** @return array{0: string, 1: string}|null */
    public static function parse(string $url): ?array
    {
        return preg_match('~codepen\.io/([\w-]+)/(?:pen|embed)/([\w-]+)~', $url, $m) ? [$m[1], $m[2]] : null;
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'CodePen · '.($config['title'] ?? '');
    }

    public static function toHtml(array $config, array $data): ?string
    {
        if (! $parsed = self::parse((string) ($config['url'] ?? ''))) {
            return null;
        }

        [$user, $pen] = $parsed;
        $title = e($config['title'] ?? 'CodePen demo');
        $height = max(200, min(1000, (int) ($config['height'] ?? 400)));
        $embed = e("https://codepen.io/{$user}/embed/{$pen}?default-tab=".urlencode($config['tab'] ?? 'result'));
        $link = e("https://codepen.io/{$user}/pen/{$pen}");

        return <<<HTML
            <figure class="pen-embed" data-embed-src="{$embed}" data-embed-title="{$title}" style="--embed-height: {$height}px">
            <a href="{$link}" class="pen-embed__poster" target="_blank" rel="noopener">Load the CodePen demo: {$title}</a>
            </figure>
            HTML;
    }
}
