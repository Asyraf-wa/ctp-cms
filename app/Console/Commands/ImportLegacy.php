<?php

namespace App\Console\Commands;

use App\Legacy\LegacyImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

// PRD MIG-01..07: import the old site from ctp_legacy (load it with ./dev legacy-import).
#[Signature('ctp:import-legacy
    {--dry-run : Report what would happen without writing anything}
    {--force : Re-import articles that were imported before (overwrites edits)}
    {--no-download : Do not fetch posters or screenshots from the network}
    {--id=* : Only these legacy article ids}')]
#[Description('Import tutorials, categories and tags from the legacy CakePHP site')]
class ImportLegacy extends Command
{
    public function handle(LegacyImporter $importer): int
    {
        $report = $importer->run(
            dryRun: (bool) $this->option('dry-run'),
            force: (bool) $this->option('force'),
            downloads: ! $this->option('no-download'),
            only: $this->option('id') ? array_map('intval', $this->option('id')) : null,
        );

        foreach ($report->counts as $group => $counts) {
            $this->components->twoColumnDetail(ucfirst($group), collect($counts)->map(fn ($n, $what) => "{$n} {$what}")->join(', '));
        }
        foreach ($report->totals as $what => $n) {
            $this->components->twoColumnDetail(ucfirst($what), (string) $n);
        }
        foreach ($report->errors as $error) {
            $this->components->error($error);
        }

        $path = storage_path('logs/legacy-import-'.now()->format('Y-m-d-His').($report->dryRun ? '-dry-run' : '').'.md');
        File::put($path, $report->toMarkdown());
        $this->components->info('Report: '.str_replace(base_path().'/', '', $path));

        if (! $report->dryRun) {
            // Pages render differently now: start with an empty full-page cache.
            $this->callSilently('responsecache:clear');
        }

        return $report->errors ? self::FAILURE : self::SUCCESS;
    }
}
