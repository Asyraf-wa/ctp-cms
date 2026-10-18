<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD CONTACT-01, CONTACT-05.
class ContactSettings extends Settings
{
    public ?string $intro;

    // Shown on the contact page, the success screen and the auto-reply, e.g. "1–3 working days".
    public string $reply_time;

    public bool $auto_reply;

    public static function group(): string
    {
        return 'contact';
    }
}
