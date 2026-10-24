<?php

use App\Settings\ScriptSettings;

const GA_SNIPPET = '<script async src="https://www.googletagmanager.com/gtag/js?id=G-TEST123"></script>';

// Analytics cookies need consent first: with the notice on, the snippets are data until the visitor accepts.
it('prints the scripts as they are while the cookie notice is off', function () {
    updateSettings(ScriptSettings::class, ['head_scripts' => GA_SNIPPET, 'cookie_notice' => false]);

    $this->get('/')->assertOk()
        ->assertSee(GA_SNIPPET, false)
        ->assertDontSee('ctp-consent-scripts', false)
        ->assertDontSee('Cookie notice', false);
});

it('keeps the scripts out of the page until the visitor accepts', function () {
    updateSettings(ScriptSettings::class, ['head_scripts' => GA_SNIPPET, 'cookie_notice' => true]);

    $html = $this->get('/')->assertOk()
        ->assertSee('aria-label="Cookie notice"', false)
        ->assertSee('Cookie settings')
        ->assertSee('id="ctp-consent-scripts"', false)
        ->getContent();

    // Not as markup: the browser must not fetch or run it. Only inside the JSON data block (tags hex-escaped).
    expect($html)->not->toContain(GA_SNIPPET)
        ->and($html)->toContain(str_replace(['<', '>', '"'], ['\\u003C', '\\u003E', '\\u0022'], '<script async src="https://www.googletagmanager.com/gtag/js?id=G-TEST123">'));
});

it('allows the Google Analytics hosts in the CSP only while consent is asked for', function () {
    updateSettings(ScriptSettings::class, ['cookie_notice' => false]);
    expect($this->get('/')->headers->get('Content-Security-Policy'))->not->toContain('googletagmanager.com');

    updateSettings(ScriptSettings::class, ['cookie_notice' => true]);
    expect($this->get('/')->headers->get('Content-Security-Policy'))
        ->toContain('https://www.googletagmanager.com')->toContain('https://*.google-analytics.com');
});
