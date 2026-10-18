<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

// ADM-01, ART-08: tutorials flagged for review or not updated for a long time.
class NeedsReview extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Needs review';

    public function table(Table $table): Table
    {
        $cutoff = now()->subMonths((int) config('ctp.freshness_months'));

        return $table
            ->query(fn (): Builder => Article::query()->published()->tutorials()
                ->where(fn (Builder $query) => $query->where('needs_review', true)
                    ->orWhereRaw('coalesce(content_updated_at, published_at) < ?', [$cutoff])))
            ->defaultSort('published_at')
            ->paginated([5])
            ->emptyStateHeading('Everything is up to date')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('title')->limit(50)->url(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('reason')->state(fn (Article $record) => $record->needs_review ? 'Flagged' : 'Not updated since '.($record->content_updated_at ?? $record->published_at)?->format('M Y'))
                    ->badge()->color(fn (Article $record) => $record->needs_review ? 'warning' : 'gray'),
            ]);
    }
}
