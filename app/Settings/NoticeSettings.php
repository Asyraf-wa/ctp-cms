<?php

namespace App\Settings;

use Carbon\CarbonImmutable;
use Spatie\LaravelSettings\Settings;

// PRD SET-03: the topbar notification above the header.
class NoticeSettings extends Settings
{
    public const STYLES = ['info' => 'Info', 'success' => 'Success', 'warning' => 'Warning', 'maintenance' => 'Maintenance'];

    public bool $enabled;

    public ?string $message;

    public string $style;

    public ?string $link_label;

    public ?string $link_url;

    public ?string $starts_at;

    public ?string $ends_at;

    public bool $dismissible;

    public static function group(): string
    {
        return 'notice';
    }

    public function isVisible(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        return $this->enabled
            && filled($this->message)
            && ($this->starts_at === null || $now->gte(CarbonImmutable::parse($this->starts_at)))
            && ($this->ends_at === null || $now->lt(CarbonImmutable::parse($this->ends_at)));
    }

    // Changes whenever the message changes, so a dismissed notice comes back when it is replaced.
    public function hash(): string
    {
        return substr(md5($this->style.'|'.$this->message.'|'.$this->link_url), 0, 12);
    }
}
