<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// ARCH §5: body HTML is rendered on save; run this after changing the render pipeline or a block's markup.
#[Signature('ctp:rerender {--id=* : Only these article ids} {--only= : article, page or project}')]
#[Description('Re-render stored article, page and project HTML, TOC and reading time')]
class RerenderArticles extends Command
{
    public function handle(): int
    {
        $queries = [
            'article' => Article::withTrashed()->when($this->option('id'), fn ($q, $ids) => $q->whereIn('id', $ids)),
            'page' => $this->option('id') ? null : Page::query(),
            'project' => $this->option('id') ? null : Project::query(),
        ];

        foreach ($queries as $type => $query) {
            if ($query === null || ($this->option('only') && $this->option('only') !== $type)) {
                continue;
            }
            $this->rerender($type, $query);
        }

        return self::SUCCESS;
    }

    /** @param Builder<covariant Model> $query */
    private function rerender(string $type, Builder $query): void
    {
        $this->components->info(ucfirst($type).'s');
        $bar = $this->output->createProgressBar($query->count());

        $query->lazyById()->each(function (Model $model) use ($bar) {
            /** @var Article|Page|Project $model */
            $model->renderBody();
            $model->saveQuietly(); // no revisions, redirects or status changes
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
    }
}
