<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD SET-06: analytics and other third-party snippets, output raw. Admin-only input.
class ScriptSettings extends Settings
{
    public ?string $head_scripts;

    public ?string $body_scripts;

    public bool $cookie_notice;

    // Hosts the Google Analytics / Tag Manager snippets talk to; added to the CSP while the cookie notice is on.
    public const ANALYTICS_HOSTS = [
        'https://www.googletagmanager.com',
        'https://*.googletagmanager.com',
        'https://*.google-analytics.com',
        'https://*.analytics.google.com',
    ];

    public static function group(): string
    {
        return 'scripts';
    }
}
