<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// Layout options for the public site that the owner can tune without code.
class DisplaySettings extends Settings
{
    // Tutorials listed under each category in the left sidebar before "View all".
    public int $sidebar_items_per_category;

    public const SIDEBAR_MAX = 20;

    public static function group(): string
    {
        return 'display';
    }
}
