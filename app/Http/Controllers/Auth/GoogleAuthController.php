<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminAccess;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

// Admin sign-in with Google (PRD AUTH-01..03). There is no password login.
class GoogleAuthController extends Controller
{
    private const REMEMBER_MINUTES = 60 * 24 * 30;

    public function redirect(): SymfonyRedirect
    {
        return $this->google()
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            /** @var GoogleUser $google */
            $google = $this->google()->user();
        } catch (Throwable $e) {
            Log::warning('Google sign-in failed', ['error' => $e->getMessage()]);

            return $this->backToLogin('Google sign-in failed. Please try again.');
        }

        $email = strtolower((string) $google->getEmail());

        if (! ($google->getRaw()['email_verified'] ?? false) || ! AdminAccess::allows($email)) {
            Log::warning('Rejected admin sign-in', ['email' => $email, 'ip' => $request->ip()]);

            return $this->backToLogin('This Google account is not allowed to access the admin.');
        }

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $google->getName() ?: $email,
            'google_id' => $google->getId(),
            'google_avatar' => $google->getAvatar(),
            'last_login_at' => now(),
        ]);

        Auth::guard('web')->setRememberDuration(self::REMEMBER_MINUTES);
        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(Filament::getPanel('admin')->getUrl());
    }

    private function google(): GoogleProvider
    {
        /** @var GoogleProvider */
        return Socialite::driver('google');
    }

    private function backToLogin(string $message): RedirectResponse
    {
        return redirect()->to(Filament::getPanel('admin')->getLoginUrl())->with('auth_error', $message);
    }
}
