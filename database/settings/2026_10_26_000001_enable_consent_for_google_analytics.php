<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

// Analytics cookies need consent first (PDPA notice, GDPR consent): if the snippets already contain Google
// Analytics / Tag Manager, turn the consent notice on. An owner who turned it off by choice can switch it off again.
return new class extends SettingsMigration
{
    public function up(): void
    {
        $scripts = fn (string $key): string => (string) DB::table('settings')->where('group', 'scripts')->where('name', $key)->value('payload');

        $text = $scripts('head_scripts').$scripts('body_scripts');

        if (preg_match('/googletagmanager\.com|google-analytics\.com|gtag\(/i', $text)) {
            $this->migrator->update('scripts.cookie_notice', fn () => true);
        }
    }
};
