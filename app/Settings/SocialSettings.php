<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD SET-05. Used in the footer, the author box fallback and Organization.sameAs (footer order = this order).
class SocialSettings extends Settings
{
    public const NETWORKS = [
        'github' => 'GitHub',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'x' => 'X (Twitter)',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'codepen' => 'CodePen',
        'freecodecamp' => 'freeCodeCamp',
        'packagist' => 'Packagist',
        'google_developers' => 'Google Developer Profile',
        'google_scholar' => 'Google Scholar',
        'orcid' => 'ORCID',
        'scopus' => 'Scopus',
    ];

    // Admin grouping: the first are social networks, the rest developer and research profiles.
    public const PROFILES = ['codepen', 'freecodecamp', 'packagist', 'google_developers', 'google_scholar', 'orcid', 'scopus'];

    public ?string $github;

    public ?string $x;

    public ?string $linkedin;

    public ?string $youtube;

    public ?string $facebook;

    public ?string $instagram;

    public ?string $tiktok;

    public ?string $codepen;

    public ?string $freecodecamp;

    public ?string $packagist;

    public ?string $google_developers;

    public ?string $google_scholar;

    public ?string $orcid;

    public ?string $scopus;

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
