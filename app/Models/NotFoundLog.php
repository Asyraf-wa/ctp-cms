<?php

namespace App\Models;

use App\Support\Visitor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SEO-11: one row per missing path with a hit counter. Scanner noise (wp-login.php, .env…) is not logged.
 *
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 */
#[Fillable(['path', 'path_hash', 'hits', 'referrer', 'is_bot', 'ignored', 'first_seen_at', 'last_seen_at'])]
class NotFoundLog extends Model
{
    public $timestamps = false;

    // Requests for files we never serve: vulnerability scanners, old CMS paths.
    private const NOISE = '#(\.(php\d?|asp|aspx|jsp|cgi|env|ini|sql|bak|old|zip|gz|tar|rar|log|yml|yaml|git|DS_Store)$)|(^/(admin|livewire|filament|wp-|wordpress|xmlrpc|\.well-known/(?!security)|\.git|cgi-bin|vendor/|phpmyadmin|admin/\.|_ignition))#i';

    protected function casts(): array
    {
        return ['is_bot' => 'boolean', 'ignored' => 'boolean', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public static function record(Request $request): void
    {
        $path = Redirect::normalizePath($request->getPathInfo());
        if (! $request->isMethod('GET') || preg_match(self::NOISE, $path) || strlen($path) > 500) {
            return;
        }

        $referrer = $request->headers->get('referer');
        $external = $referrer && parse_url($referrer, PHP_URL_HOST) !== $request->getHost() ? Str::limit($referrer, 497) : null;
        $now = now();

        // One statement, so concurrent 404s cannot race on the unique hash.
        DB::table('not_found_logs')->upsert(
            [['path_hash' => sha1($path), 'path' => $path, 'hits' => 1, 'referrer' => $external, 'is_bot' => Visitor::isBot($request), 'first_seen_at' => $now, 'last_seen_at' => $now]],
            ['path_hash'],
            ['hits' => DB::raw('hits + 1'), 'last_seen_at' => $now, 'referrer' => DB::raw('coalesce(values(referrer), referrer)')],
        );
    }

    // Turn this 404 into a redirect and drop it from the log.
    public function redirectTo(?string $target, int $status = 301): ?Redirect
    {
        $redirect = Redirect::record($this->path, $target, Redirect::SOURCE_MANUAL, $status);
        $this->delete();

        return $redirect;
    }
}
