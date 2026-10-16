<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

// Defaults come from the legacy CakePHP `settings` row (PRD SET-00 / MIG-02).
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Code The Pixel');
        $this->migrator->add('general.site_abbr', 'CTP');
        $this->migrator->add('general.slogan', 'Code The Experiences');
        $this->migrator->add('general.description', 'CodeThePixel.com (CTP) is a project aimed at assisting aspiring programmers with coding. CTP prioritises delivering highly optimised code or snippets, with a focus on simplicity.');
        $this->migrator->add('general.organization_name', 'Code The Pixel Inc.');
        $this->migrator->add('general.domain_name', 'codethepixel.com');
        $this->migrator->add('general.contact_email', 'noreply@codethepixel.com');
        $this->migrator->add('general.notification_email', 'noreply@codethepixel.com');
        $this->migrator->add('general.copyright', 'Code The Pixel by Asyraf Wahi Anuar');
        $this->migrator->add('general.logo_light', null);
        $this->migrator->add('general.logo_dark', null);
        $this->migrator->add('general.default_og_image', null);
        $this->migrator->add('general.default_author_id', null);

        $this->migrator->add('seo.title_format', '{page} · {site}');
        $this->migrator->add('seo.meta_title', 'Code The Pixel');
        $this->migrator->add('seo.meta_description', 'CodeThePixel.com (CTP) is a project aimed at assisting aspiring programmers with coding. CTP prioritises delivering highly optimised code or snippets, with a focus on simplicity.');
        $this->migrator->add('seo.meta_keywords', 'Code The Pixel, Re-CRUD, CakePHP, Learning, CRUD, Coding, Learn code, CakePHP 5, Asyraf, Asyraf Wahi Anuar');
        $this->migrator->add('seo.meta_copyright', 'Code The Pixel by Asyraf Wahi Anuar');
        $this->migrator->add('seo.meta_author', 'Asyraf Wahi Anuar');
        $this->migrator->add('seo.default_robots', 'index, follow');
        $this->migrator->add('seo.google_verification', null);
        $this->migrator->add('seo.bing_verification', null);

        $this->migrator->add('notice.enabled', false);
        $this->migrator->add('notice.message', null);
        $this->migrator->add('notice.style', 'info');
        $this->migrator->add('notice.link_label', null);
        $this->migrator->add('notice.link_url', null);
        $this->migrator->add('notice.starts_at', null);
        $this->migrator->add('notice.ends_at', null);
        $this->migrator->add('notice.dismissible', true);

        $this->migrator->add('ribbon.enabled', false);
        $this->migrator->add('ribbon.title', 'Code The Pixel');
        $this->migrator->add('ribbon.url', 'https://codethepixel.com');
        $this->migrator->add('ribbon.new_tab', false);
        $this->migrator->add('ribbon.bg_color', '#0f766e');
        $this->migrator->add('ribbon.text_color', 'auto');

        $this->migrator->add('social.github', 'https://github.com/Asyraf-wa');
        $this->migrator->add('social.x', null);
        $this->migrator->add('social.linkedin', null);
        $this->migrator->add('social.youtube', null);
        $this->migrator->add('social.facebook', null);
        $this->migrator->add('social.instagram', null);
        $this->migrator->add('social.tiktok', null);

        $this->migrator->add('scripts.head_scripts', null);
        $this->migrator->add('scripts.body_scripts', null);
        $this->migrator->add('scripts.cookie_notice', false);

        $this->migrator->add('integrations.comments_enabled', true);
        $this->migrator->add('integrations.comments_auto_close_days', null);
        $this->migrator->add('integrations.turnstile_site_key', null);
        $this->migrator->addEncrypted('integrations.turnstile_secret_key', null);
        $this->migrator->add('integrations.indexnow_key', null);
        $this->migrator->add('integrations.ai_crawlers', [
            'GPTBot' => true, 'ChatGPT-User' => true, 'ClaudeBot' => true,
            'PerplexityBot' => true, 'Google-Extended' => true, 'CCBot' => false,
        ]);
    }
};
