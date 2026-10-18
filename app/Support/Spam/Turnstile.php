<?php

namespace App\Support\Spam;

use App\Settings\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Throwable;

// CMT-02, CONTACT-04: Cloudflare Turnstile, used only when both keys are set in Site settings → Integrations.
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function siteKey(): ?string
    {
        $settings = app(IntegrationSettings::class);

        return filled($settings->turnstile_site_key) && filled($settings->turnstile_secret_key) ? $settings->turnstile_site_key : null;
    }

    public static function verify(?string $token, ?string $ip): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(8)->post(self::VERIFY_URL, [
                'secret' => app(IntegrationSettings::class)->turnstile_secret_key,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) $response->json('success');
        } catch (Throwable) {
            // Cloudflare unreachable: let the other checks decide rather than blocking every reader.
            return true;
        }
    }
}
