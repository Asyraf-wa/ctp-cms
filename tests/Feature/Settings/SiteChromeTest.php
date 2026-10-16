<?php

use App\Settings\GeneralSettings;
use App\Settings\NoticeSettings;
use App\Settings\RibbonSettings;
use App\Settings\ScriptSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;

function updateSettings(string $class, array $values): void
{
    app($class)->fill($values)->save();
}

it('renders the site name, slogan and meta tags from settings', function () {
    updateSettings(GeneralSettings::class, ['site_name' => 'Pixel Lab', 'slogan' => 'Build things', 'copyright' => 'Pixel Lab Sdn Bhd']);
    updateSettings(SeoSettings::class, ['meta_keywords' => 'laravel, cakephp', 'meta_author' => 'Asyraf', 'google_verification' => 'abc123']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Pixel Lab')
        ->assertSee('Build things')
        ->assertSee('Pixel Lab Sdn Bhd. All rights reserved.')
        ->assertSee('<meta name="keywords" content="laravel, cakephp">', false)
        ->assertSee('<meta name="author" content="Asyraf">', false)
        ->assertSee('<meta name="google-site-verification" content="abc123">', false);
});

it('formats page titles with the title format', function () {
    updateSettings(SeoSettings::class, ['title_format' => '{page} | {site}']);

    $this->get('/tutorials')->assertSee('<title>All tutorials | Code The Pixel</title>', false);
});

it('shows the topbar notice only when enabled and inside its window', function () {
    $this->get('/')->assertDontSee('id="site-notice"', false);

    updateSettings(NoticeSettings::class, ['enabled' => true, 'message' => '<strong>Maintenance</strong> tonight', 'style' => 'maintenance']);
    $this->get('/')->assertSee('id="site-notice"', false)->assertSee('<strong>Maintenance</strong> tonight', false);

    updateSettings(NoticeSettings::class, ['starts_at' => now()->addDay()->toDateTimeString()]);
    $this->get('/')->assertDontSee('id="site-notice"', false);
});

it('escapes anything unsafe that reaches the notice message', function () {
    updateSettings(NoticeSettings::class, ['enabled' => true, 'message' => 'Hi <img src=x onerror=alert(1)><script>alert(1)</script>']);

    $this->get('/')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('onerror', false);
});

it('renders the ribbon with its colours and readable text', function () {
    $this->get('/')->assertDontSee('NEW SERIES');

    updateSettings(RibbonSettings::class, ['enabled' => true, 'title' => 'New series', 'url' => 'https://example.com', 'bg_color' => '#facc15', 'text_color' => 'auto']);

    $this->get('/')
        ->assertSee('New series')
        ->assertSee('background-color: #facc15; color: #0f172a;', false)
        ->assertSee('href="https://example.com"', false);
});

it('outputs analytics scripts and filled social links', function () {
    updateSettings(ScriptSettings::class, ['head_scripts' => '<script data-test="head"></script>']);
    updateSettings(SocialSettings::class, ['youtube' => 'https://youtube.com/@codethepixel', 'x' => null]);

    $this->get('/')
        ->assertSee('<script data-test="head"></script>', false)
        ->assertSee('href="https://youtube.com/@codethepixel"', false)
        ->assertDontSee('X (Twitter)');
});
