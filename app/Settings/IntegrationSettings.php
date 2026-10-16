<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

// PRD SET-07.
class IntegrationSettings extends Settings
{
    // GEO-05: robots.txt policy per AI crawler (true = allowed).
    public const AI_CRAWLERS = [
        'GPTBot' => 'OpenAI GPTBot',
        'ChatGPT-User' => 'ChatGPT browsing',
        'ClaudeBot' => 'Anthropic ClaudeBot',
        'PerplexityBot' => 'PerplexityBot',
        'Google-Extended' => 'Google AI (Gemini) training',
        'CCBot' => 'Common Crawl',
    ];

    public bool $comments_enabled;

    public ?int $comments_auto_close_days;

    public ?string $turnstile_site_key;

    public ?string $turnstile_secret_key;

    public ?string $indexnow_key;

    /** @var array<string, bool> */
    public array $ai_crawlers;

    public static function group(): string
    {
        return 'integrations';
    }

    /** @return list<string> */
    public static function encrypted(): array
    {
        return ['turnstile_secret_key'];
    }
}
