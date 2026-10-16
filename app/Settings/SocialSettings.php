<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD SET-05. Used in the footer, the author box fallback and Organization.sameAs.
class SocialSettings extends Settings
{
    public const NETWORKS = [
        'github' => 'GitHub',
        'x' => 'X (Twitter)',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
    ];

    public ?string $github;

    public ?string $x;

    public ?string $linkedin;

    public ?string $youtube;

    public ?string $facebook;

    public ?string $instagram;

    public ?string $tiktok;

    public static function group(): string
    {
        return 'social';
    }

    /** @return array<string, string> network => url, only the filled ones */
    public function links(): array
    {
        return collect(array_keys(self::NETWORKS))
            ->mapWithKeys(fn (string $network): array => [$network => $this->{$network}])
            ->filter()
            ->all();
    }
}
