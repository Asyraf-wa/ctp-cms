<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\Redirect;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->user = User::factory()->create(['email' => 'owner@example.com', 'name' => 'Asyraf Wahi Anuar']);
    $this->actingAs($this->user);
});

it('saves the public author profile', function () {
    Livewire::test(EditProfile::class)
        ->fillForm([
            'display_name' => 'Asyraf',
            'job_title' => 'Senior Lecturer',
            'bio_short' => 'I write about CakePHP and Laravel.',
            'socials.github' => 'https://github.com/Asyraf-wa',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->user->refresh();
    expect($this->user->authorName())->toBe('Asyraf')
        ->and($this->user->job_title)->toBe('Senior Lecturer')
        ->and($this->user->socials['github'])->toBe('https://github.com/Asyraf-wa');
});

it('keeps the Google name and email read-only', function () {
    Livewire::test(EditProfile::class)
        ->fillForm(['name' => 'Someone Else', 'email' => 'other@example.com'])
        ->call('save');

    expect($this->user->refresh()->only('name', 'email'))->toBe(['name' => 'Asyraf Wahi Anuar', 'email' => 'owner@example.com']);
});

it('redirects the old author URL when the profile URL changes', function () {
    Livewire::test(EditProfile::class)->fillForm(['slug' => 'asyraf'])->call('save')->assertHasNoFormErrors();

    expect(Redirect::sole()->only('from_path', 'to_path'))->toBe(['from_path' => '/author/asyraf-wahi-anuar', 'to_path' => '/author/asyraf']);
});

it('rejects an invalid or taken profile URL', function () {
    User::factory()->create(['name' => 'Taken Name']);

    Livewire::test(EditProfile::class)->fillForm(['slug' => 'Not Valid!'])->call('save')->assertHasFormErrors(['slug']);
    Livewire::test(EditProfile::class)->fillForm(['slug' => 'taken-name'])->call('save')->assertHasFormErrors(['slug']);
});
