<?php

namespace App\Settings;

use App\Support\Contrast;
use Spatie\LaravelSettings\Settings;

// PRD SET-04: the diagonal ribbon in the top-right corner.
class RibbonSettings extends Settings
{
    public bool $enabled;

    public ?string $title;

    public ?string $url;

    public bool $new_tab;

    public string $bg_color;

    // 'auto' picks white or near-black, whichever has more contrast with bg_color.
    public string $text_color;

    public static function group(): string
    {
        return 'ribbon';
    }

    public function isVisible(): bool
    {
        return $this->enabled && filled($this->title);
    }

    public function resolvedTextColor(): string
    {
        return $this->text_color === 'auto' ? Contrast::readableOn($this->bg_color) : $this->text_color;
    }
}
