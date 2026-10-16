<?php

namespace App\Jobs;

use App\Settings\IntegrationSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// SEO-08: one IndexNow request for a batch of URLs on this host.
class SubmitToIndexNow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /** @param  list<string>  $urls */
    public function __construct(public array $urls) {}

    public function handle(IntegrationSettings $settings): void
    {
        $key = $settings->indexnow_key;
        if (blank($key) || $this->urls === []) {
            return;
        }

        $response = Http::timeout(10)->acceptJson()->post(config('ctp.seo.indexnow_endpoint'), [
            'host' => parse_url(url('/'), PHP_URL_HOST),
            'key' => $key,
            'keyLocation' => url("/{$key}.txt"),
            'urlList' => $this->urls,
        ]);

        // 200/202 accepted; 422 means the URLs are not on this host (wrong APP_URL). Retry only server errors.
        if ($response->serverError()) {
            $response->throw();
        }
        if ($response->clientError()) {
            Log::warning('IndexNow rejected the submission', ['status' => $response->status(), 'urls' => $this->urls]);
        }
    }
}
