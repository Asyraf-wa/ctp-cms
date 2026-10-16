<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// ARCH §5: article HTML is rendered on save; run this after changing the render pipeline or a block's markup.
#[Signature('ctp:rerender {--id=* : Only these article ids}')]
#[Description('Re-render stored article HTML, TOC and reading time')]
class RerenderArticles extends Command
{
    public function handle(): int
    {
        $query = Article::withTrashed()->when($this->option('id'), fn ($q, $ids) => $q->whereIn('id', $ids));
        $bar = $this->output->createProgressBar($query->count());

        $query->lazyById()->each(function (Article $article) use ($bar) {
            $article->renderBody();
            $article->saveQuietly(); // no revisions, redirects or status changes
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        return self::SUCCESS;
    }
}
