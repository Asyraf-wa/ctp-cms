<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// ART-10: fold finished days of article_views into article_view_stats and drop the raw rows.
#[Signature('ctp:rollup-views')]
#[Description('Roll up per-visitor article views into daily totals and prune them')]
class RollupViews extends Command
{
    public function handle(): int
    {
        $today = now()->toDateString();

        $rows = DB::table('article_views')
            ->where('viewed_on', '<', $today)
            ->selectRaw('article_id, viewed_on as date, count(*) as views')
            ->groupBy('article_id', 'viewed_on')
            ->get()
            ->map(fn ($row) => ['article_id' => $row->article_id, 'date' => $row->date, 'views' => $row->views])
            ->all();

        DB::transaction(function () use ($rows, $today) {
            if ($rows) {
                // Raw rows are deleted in the same transaction, so a day is only ever rolled up once.
                DB::table('article_view_stats')->upsert($rows, ['article_id', 'date'], ['views']);
            }
            DB::table('article_views')->where('viewed_on', '<', $today)->delete();
        });

        $this->components->info(count($rows).' daily totals rolled up.');

        return self::SUCCESS;
    }
}
