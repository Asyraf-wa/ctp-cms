<?php

namespace App\Content\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

// PRD EDIT-05: privacy-friendly embed — a thumbnail until clicked, then youtube-nocookie.
class YouTubeBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'youtube';
    }

    public static function getLabel(): string
    {
        return 'YouTube video';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('url')->label('YouTube URL')->required()->url()
                ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail) {
                    if (! self::videoId((string) $value)) {
                        $fail('That does not look like a YouTube video link.');
                    }
                }),
            TextInput::make('title')->required()->helperText('Describes the video for screen readers and search engines.'),
            TextInput::make('start')->label('Start at (seconds)')->numeric()->minValue(0),
        ]);
    }

    public static function videoId(string $url): ?string
    {
        return preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $url, $m) ? $m[1] : null;
    }

    public static function getPreviewLabel(array $config): string
    {
        return 'YouTube · '.($config['title'] ?? '');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $id = self::videoId((string) ($config['url'] ?? ''));

        return $id ? '<img src="https://i.ytimg.com/vi/'.$id.'/mqdefault.jpg" alt="" style="max-width:320px;border-radius:8px">' : null;
    }

    public static function toHtml(array $config, array $data): ?string
    {
        $id = self::videoId((string) ($config['url'] ?? ''));
        if (! $id) {
            return null;
        }

        $title = e($config['title'] ?? 'YouTube video');
        $start = (int) ($config['start'] ?? 0);
        $embed = e("https://www.youtube-nocookie.com/embed/{$id}?autoplay=1".($start ? "&start={$start}" : ''));
        $watch = e("https://www.youtube.com/watch?v={$id}".($start ? "&t={$start}s" : ''));

        return <<<HTML
            <figure class="video-embed" data-embed-src="{$embed}" data-embed-title="{$title}">
            <a href="{$watch}" class="video-embed__poster" target="_blank" rel="noopener" aria-label="Play video: {$title}">
            <img src="https://i.ytimg.com/vi/{$id}/hqdefault.jpg" alt="" loading="lazy" width="480" height="360">
            <span class="video-embed__play" aria-hidden="true"></span>
            </a>
            <figcaption>{$title}</figcaption>
            </figure>
            HTML;
    }
}
