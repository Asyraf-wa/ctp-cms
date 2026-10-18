<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD SET-01. Pre-filled from the legacy `settings` row.
class GeneralSettings extends Settings
{
    public string $site_name;

    public string $site_abbr;

    public ?string $slogan;

    public ?string $description;

    public ?string $organization_name;

    public string $domain_name;

    public ?string $contact_email;

    public ?string $notification_email;

    public ?string $copyright;

    public ?string $logo_light;

    public ?string $logo_dark;

    public ?string $default_og_image;

    public ?int $default_author_id;

    public static function group(): string
    {
        return 'general';
    }
}
