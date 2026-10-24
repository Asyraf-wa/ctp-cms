<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

// SEO health check: a 14-character home title is too short to say what the site is about.
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('seo.meta_title', function (?string $title): string {
            $plain = fn (?string $t) => strtolower(preg_replace('/\s+/', '', (string) $t));

            // Only replace an empty title or the bare site name; anything written by hand stays.
            return ($title === null || $title === '' || in_array($plain($title), ['codethepixel', 'ctp'], true))
                ? 'Code The Pixel: PHP, Laravel and CakePHP Tutorials'
                : $title;
        });
    }
};
