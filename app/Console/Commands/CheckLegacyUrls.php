<?php

namespace App\Console\Commands;

use App\Legacy\LegacyImporter;
use App\Models\Redirect;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * T5.09, MIG-05: every URL of the old site must answer 301 → 200 (or 410 for the retired blog posts).
 * Requests go through the app itself, so no web server is needed.
 */
#[Signature('ctp:check-legacy-urls {--show-ok : List the URLs that pass too}')]
#[Description('Check that every legacy URL redirects to a working page')]
class CheckLegacyUrls extends Command
{
    public function handle(Kernel $kernel): int
    {
        config(['database.connections.legacy' => ['database' => config('ctp.legacy_database')] + config('database.connections.mysql')]);
        $legacy = DB::connection('legacy');

        $urls = [];
        foreach ($legacy->table('articles')->orderBy('id')->get() as $row) {
            $gone = (int) $row->category_id === LegacyImporter::BLOG_CATEGORY && ! isset(LegacyImporter::BLOG_REDIRECTS[$row->slug]);
            foreach (['/codethepixel/articles/', '/articles/'] as $prefix) {
                $urls[$prefix.$row->slug] = $gone ? 410 : 200;
            }
            if ($row->poster && (int) $row->category_id !== LegacyImporter::BLOG_CATEGORY) {
                $urls['/files/Articles/poster/'.rawurlencode(basename(str_replace('\\', '/', (string) $row->poster_dir))).'/'.rawurlencode((string) $row->poster)] = 200;
            }
        }
        foreach ($legacy->table('tags_tags')->where('counter', '>', 0)->pluck('slug') as $slug) {
            $urls['/codethepixel/articles?tag='.rawurlencode((string) $slug)] = 200;
        }
        foreach (['/codethepixel', '/codethepixel?page=2', '/codethepixel/articles', '/codethepixel/articles?category=1'] as $url) {
            $urls[$url] = 200;
        }

        $failures = [];
        $bar = $this->output->createProgressBar(count($urls));
        foreach ($urls as $url => $expected) {
            [$status, $final, $hops] = $this->follow($kernel, $url);
            $ok = $expected === 410 ? $status === 410 : ($status === 200 && $hops >= 1);
            if (! $ok) {
                $failures[] = [$url, "{$status} after {$hops} hop(s)", $final];
            } elseif ($this->option('show-ok')) {
                $this->line(" ✓ {$url} → {$final}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        // The check itself must not count as real traffic on the redirects.
        Redirect::where('source', Redirect::SOURCE_LEGACY)->update(['hits' => 0, 'last_hit_at' => null]);

        if ($failures) {
            $this->table(['Legacy URL', 'Result', 'Ended at'], $failures);
            $this->components->error(count($failures).' of '.count($urls).' legacy URLs do not reach a page.');

            return self::FAILURE;
        }
        $this->components->info('All '.count($urls).' legacy URLs redirect to a working page (retired blog posts answer 410).');

        return self::SUCCESS;
    }

    /** @return array{0: int, 1: string, 2: int} final status, final URL, number of redirects */
    private function follow(Kernel $kernel, string $url): array
    {
        $hops = 0;
        while (true) {
            // Media files are served by the web server, not the app: check the file itself.
            if ($hops > 0 && preg_match('#^/(storage|media)/#', $url)) {
                return [is_file(public_path(rawurldecode((string) parse_url($url, PHP_URL_PATH)))) ? 200 : 404, $url, $hops];
            }
            $response = $kernel->handle(Request::create($url, 'GET', server: ['HTTP_USER_AGENT' => 'CTP legacy URL check']));
            $status = $response->getStatusCode();
            if (! $response->isRedirection() || $hops >= 5) {
                return [$status, $url, $hops];
            }
            $location = (string) $response->headers->get('Location');
            $url = str_starts_with($location, 'http') ? ((string) parse_url($location, PHP_URL_PATH)).(($q = parse_url($location, PHP_URL_QUERY)) ? '?'.$q : '') : $location;
            $hops++;
        }
    }
}
