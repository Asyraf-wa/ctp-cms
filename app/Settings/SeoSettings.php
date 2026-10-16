<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD SET-02. Site-wide defaults; each page can override title, description and robots.
class SeoSettings extends Settings
{
    public string $title_format;

    public ?string $meta_title;

    public ?string $meta_description;

    public ?string $meta_keywords;

    public ?string $meta_copyright;

    public ?string $meta_author;

    public string $default_robots;

    public ?string $google_verification;

    public ?string $bing_verification;

    // SEO-06: extra robots.txt lines (rules for other bots, extra Disallow paths).
    public ?string $robots_extra;

    public static function group(): string
    {
        return 'seo';
    }
}
