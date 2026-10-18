<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

// Google-only sign-in (PRD AUTH-01) plus a local-only Quick login (AUTH-04).
class Login extends BaseLogin
{
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Callout::make(fn (): ?string => session('auth_error'))
                ->danger()
                ->visible(fn (): bool => session()->has('auth_error')),
            Actions::make([
                $this->googleAction(),
                $this->quickLoginAction(),
            ])->fullWidth(),
            Text::make('Access is limited to approved accounts.')
                ->color('gray'),
        ]);
    }

    // The password form is never rendered; this blocks a crafted Livewire call to it as well.
    public function authenticate(): ?LoginResponse
    {
        abort(404);
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Sign in to '.config('app.name');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function googleAction(): Action
    {
        return Action::make('google')
            ->label('Continue with Google')
            ->icon('heroicon-o-arrow-right-end-on-rectangle')
            ->url(route('auth.google.redirect'));
    }

    protected function quickLoginAction(): Action
    {
        return Action::make('quickLogin')
            ->label('Quick login (local)')
            ->color('gray')
            ->outlined()
            ->visible(fn (): bool => app()->isLocal() && filled(config('ctp.admin_emails')))
            ->action(function () {
                abort_unless(app()->isLocal(), 404);

                $email = config('ctp.admin_emails')[0];
                $user = User::firstOrCreate(['email' => $email], ['name' => 'Asyraf Wahi Anuar']);

                Filament::auth()->login($user, remember: true);
                session()->regenerate();

                return redirect()->intended(Filament::getUrl());
            });
    }
}
