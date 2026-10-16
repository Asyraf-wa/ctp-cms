<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // SEO-06: extra lines appended to the generated robots.txt.
        $this->migrator->add('seo.robots_extra', null);
    }
};
