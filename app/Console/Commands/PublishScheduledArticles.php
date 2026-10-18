<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// PRD ART-02: runs every minute from the scheduler (cron on shared hosting, ARCH §9).
#[Signature('ctp:publish-scheduled')]
#[Description('Publish scheduled articles whose publish date has passed')]
class PublishScheduledArticles extends Command
{
    public function handle(): int
    {
        $due = Article::where('status', ArticleStatus::Scheduled)->where('published_at', '<=', now())->get();

        foreach ($due as $article) {
            // saving() turns a past publish date into Published (and flushes menus/sidebar caches).
            $article->update(['status' => ArticleStatus::Published]);
            $this->components->info("Published: {$article->title}");
        }

        return self::SUCCESS;
    }
}
