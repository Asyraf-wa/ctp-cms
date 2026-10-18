<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery\MockInterface;

beforeEach(function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
});

function fakeGoogleUser(string $email, bool $verified = true): void
{
    $googleUser = (new GoogleUser)->setRaw(['email_verified' => $verified])->map([
        'id' => 'google-123',
        'name' => 'Owner',
        'email' => $email,
        'avatar' => 'https://example.com/avatar.png',
    ]);

    fakeGoogleProvider()->shouldReceive('user')->andReturn($googleUser);
}

function fakeGoogleProvider(): MockInterface
{
    $provider = Mockery::mock(GoogleProvider::class);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    return $provider;
}

it('signs in an allow-listed Google account and creates the user', function () {
    fakeGoogleUser('Owner@Example.com');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(Filament::getPanel('admin')->getUrl());

    $user = User::sole();
    expect($user->email)->toBe('owner@example.com')
        ->and($user->google_id)->toBe('google-123')
        ->and($user->last_login_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('rejects a Google account that is not allow-listed', function () {
    fakeGoogleUser('stranger@example.com');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(Filament::getPanel('admin')->getLoginUrl())
        ->assertSessionHas('auth_error', 'This Google account is not allowed to access the admin.');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

it('rejects an allow-listed email that Google has not verified', function () {
    fakeGoogleUser('owner@example.com', verified: false);

    $this->get(route('auth.google.callback'))
        ->assertSessionHas('auth_error', 'This Google account is not allowed to access the admin.');

    $this->assertGuest();
});

it('handles a failed Google callback without an error page', function () {
    fakeGoogleProvider()->shouldReceive('user')->andThrow(new RuntimeException('invalid state'));

    $this->get(route('auth.google.callback'))
        ->assertSessionHas('auth_error', 'Google sign-in failed. Please try again.');
});

it('locks out a user whose email is removed from the allow-list', function () {
    $user = User::factory()->create(['email' => 'former@example.com']);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('lets an allow-listed user into the admin', function () {
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('shows the login page with Google sign-in only', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Continue with Google')
        ->assertDontSee('Quick login')
        ->assertDontSee('type="password"', escape: false);
});

it('quick-logs in the first admin in the local environment only', function () {
    app()->detectEnvironment(fn () => 'local');

    Livewire\Livewire::test(Login::class)
        ->assertActionVisible('quickLogin')
        ->callAction('quickLogin');

    $this->assertAuthenticatedAs(User::where('email', 'owner@example.com')->sole());
});

it('refuses the password login endpoint', function () {
    Livewire\Livewire::test(Login::class)
        ->call('authenticate')
        ->assertStatus(404);
});
