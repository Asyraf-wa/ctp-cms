<?php

use App\Filament\Pages\ManageSettings;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\IntegrationSettings;
use App\Settings\NoticeSettings;
use App\Settings\RibbonSettings;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
});

it('loads the legacy defaults into the form', function () {
    Livewire::test(ManageSettings::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'general.site_name' => 'Code The Pixel',
            'general.site_abbr' => 'CTP',
            'general.slogan' => 'Code The Experiences',
            'ribbon.bg_color' => '#0f766e',
        ]);
});

it('saves every settings group', function () {
    Livewire::test(ManageSettings::class)
        ->fillForm([
            'general.site_name' => 'CTP Test',
            'seo.meta_keywords' => 'laravel, filament',
            'ribbon.enabled' => true,
            'ribbon.title' => 'New series',
            'ribbon.bg_color' => '#b45309',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(GeneralSettings::class)->site_name)->toBe('CTP Test')
        ->and(app(RibbonSettings::class)->bg_color)->toBe('#b45309')
        ->and(app(RibbonSettings::class)->isVisible())->toBeTrue();
});

it('sanitises the notification message down to inline formatting', function () {
    Livewire::test(ManageSettings::class)
        ->fillForm([
            'notice.enabled' => true,
            'notice.message' => '<p><strong>Maintenance</strong> tonight <a href="javascript:alert(1)">x</a><script>alert(1)</script> <a href="https://codethepixel.com">details</a></p>',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $message = app(NoticeSettings::class)->message;

    expect($message)->toContain('<strong>Maintenance</strong>')
        ->toContain('href="https://codethepixel.com"')
        ->not->toContain('<script')
        ->not->toContain('javascript:')
        ->not->toContain('<p>');
});

it('stores the AI crawler policy for every known bot', function () {
    Livewire::test(ManageSettings::class)
        ->fillForm(['integrations.ai_crawlers' => ['ClaudeBot']])
        ->call('save')
        ->assertHasNoFormErrors();

    $policy = app(IntegrationSettings::class)->ai_crawlers;

    expect($policy)->toHaveKeys(array_keys(IntegrationSettings::AI_CRAWLERS))
        ->and($policy['ClaudeBot'])->toBeTrue()
        ->and($policy['GPTBot'])->toBeFalse();
});

it('keeps the Turnstile secret encrypted at rest', function () {
    Livewire::test(ManageSettings::class)
        ->fillForm(['integrations.turnstile_secret_key' => 'super-secret'])
        ->call('save');

    $raw = DB::table('settings')->where('group', 'integrations')->where('name', 'turnstile_secret_key')->value('payload');

    expect($raw)->not->toContain('super-secret')
        ->and(app(IntegrationSettings::class)->turnstile_secret_key)->toBe('super-secret');
});

it('rejects an invalid ribbon colour', function () {
    Livewire::test(ManageSettings::class)
        ->fillForm(['ribbon.bg_color' => 'red'])
        ->call('save')
        ->assertHasFormErrors(['ribbon.bg_color']);
});

it('hides a scheduled notice outside its window', function () {
    $notice = app(NoticeSettings::class);
    $notice->enabled = true;
    $notice->message = 'Maintenance';
    $notice->starts_at = '2026-10-20 01:00:00';
    $notice->ends_at = '2026-10-20 04:00:00';

    expect($notice->isVisible(CarbonImmutable::parse('2026-10-20 00:59')))->toBeFalse()
        ->and($notice->isVisible(CarbonImmutable::parse('2026-10-20 02:00')))->toBeTrue()
        ->and($notice->isVisible(CarbonImmutable::parse('2026-10-20 04:00')))->toBeFalse();
});
