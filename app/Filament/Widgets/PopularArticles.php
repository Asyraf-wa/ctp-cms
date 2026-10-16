<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

// ART-10, ART-12: which tutorials work. Kudos is the quality signal; 30-day views show what is read now.
class PopularArticles extends TableWidget
{
    protected static ?int $sort = 2;

    // ADM-01: top articles over 7 and 30 days, and the kudos signal.

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Most helpful articles';

    public function table(Table $table): Table
    {
        $since = now()->subDays(30)->toDateString();
        $week = now()->subDays(7)->toDateString();

        return $table
            ->query(fn (): Builder => Article::query()->published()
                ->select(['id', 'title', 'slug', 'type', 'kudos', 'views', 'published_at'])
                ->selectSub(DB::table('article_view_stats')->selectRaw('coalesce(sum(views), 0)')
                    ->whereColumn('article_id', 'articles.id')->where('date', '>=', $since), 'views_30d')
                ->selectSub(DB::table('article_view_stats')->selectRaw('coalesce(sum(views), 0)')
                    ->whereColumn('article_id', 'articles.id')->where('date', '>=', $week), 'views_7d'))
            ->defaultSort('kudos', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('title')->limit(70)->searchable()
                    ->url(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('kudos')->label('Kudos')->numeric()->sortable()->icon('heroicon-o-hand-thumb-up'),
                TextColumn::make('views_7d')->label('Views (7 days)')->numeric()->sortable(),
                TextColumn::make('views_30d')->label('Views (30 days)')->numeric()->sortable(),
                TextColumn::make('views')->label('Views (all time)')->numeric()->sortable()->color('gray'),
            ]);
    }
}
