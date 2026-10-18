<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Settings\IntegrationSettings;
use Illuminate\Http\Response;

// SEO-08: IndexNow verifies ownership by fetching /{key}.txt, which must contain the key.
class IndexNowKeyController extends Controller
{
    public function __invoke(string $key, IntegrationSettings $settings): Response
    {
        abort_unless(filled($settings->indexnow_key) && hash_equals((string) $settings->indexnow_key, $key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
