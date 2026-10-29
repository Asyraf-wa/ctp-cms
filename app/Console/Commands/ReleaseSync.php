<?php

namespace App\Console\Commands;

use App\Support\Release;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// DEPLOY §10: without a Terminal "Update from Remote" only moves files. The scheduler runs this each minute and,
// when the checked-out commit changed, does what .cpanel.yml does: migrate, rebuild caches, drop cached pages.
#[Signature('ctp:release-sync')]
#[Description('Run the post-deploy steps once when the checked-out release changed')]
class ReleaseSync extends Command
{
    private const MARKER = 'release.txt';

    public function handle(): int
    {
        // Only an explicit false switches it off: right after a pull the cached config may not know this key yet,
        // and this command is what rebuilds that cache.
        if (config('ctp.release_sync', true) === false) {
            return self::SUCCESS;
        }

        $release = Release::id();
        $disk = Storage::disk('local');

        if ($release === null || $disk->exists(self::MARKER) && trim($disk->get(self::MARKER)) === $release) {
            return self::SUCCESS;
        }

        $this->components->info('New release '.substr($release, 0, 7).': running post-deploy steps');

        foreach (['migrate' => ['--force' => true], 'settings:clear-cache' => [], 'optimize' => [], 'responsecache:clear' => []] as $command => $options) {
            $code = $this->call($command, $options);

            if ($code !== self::SUCCESS) {
                $this->components->error("{$command} failed; will retry next minute");

                return self::FAILURE;
            }
        }

        $disk->put(self::MARKER, $release);

        return self::SUCCESS;
    }
}
