<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

// "Generate with AI" for article key takeaways. The key is encrypted at rest like the Turnstile secret.
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->addEncrypted('integrations.deepseek_api_key', null);
        $this->migrator->add('integrations.deepseek_model', null);
    }
};
