<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Settings\IntegrationSettings;
use App\Settings\SeoSettings;
use Illuminate\Http\Response;

// SEO-06, GEO-05: robots.txt from settings. Outside production everything is blocked.
class RobotsController extends Controller
{
    public function __invoke(SeoSettings $seo, IntegrationSettings $integrations): Response
    {
        $lines = [];

        if (! config('ctp.seo.allow_indexing')) {
            $lines = ['# Not the production site: nothing here should be indexed.', 'User-agent: *', 'Disallow: /'];
        } else {
            $lines = ['User-agent: *', 'Allow: /', 'Disallow: /admin', 'Disallow: /preview/', 'Disallow: /search', 'Disallow: /api/', 'Disallow: /livewire/', ''];

            $lines[] = '# AI crawlers (Site settings → Integrations)';
            foreach (IntegrationSettings::AI_CRAWLERS as $bot => $label) {
                $allowed = $integrations->ai_crawlers[$bot] ?? true;
                array_push($lines, "User-agent: {$bot}", $allowed ? 'Allow: /' : 'Disallow: /', '');
            }

            if (filled($seo->robots_extra)) {
                array_push($lines, '# Extra rules (Site settings → SEO)', trim(str_replace("\r\n", "\n", (string) $seo->robots_extra)), '');
            }
        }

        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
