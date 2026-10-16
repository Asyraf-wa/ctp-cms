<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD SET-06: analytics and other third-party snippets, output raw. Admin-only input.
class ScriptSettings extends Settings
{
    public ?string $head_scripts;

    public ?string $body_scripts;

    public bool $cookie_notice;

    public static function group(): string
    {
        return 'scripts';
    }
}
