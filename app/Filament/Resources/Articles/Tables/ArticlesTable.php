<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Models\Article;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

// PRD ART-11.
class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        $tz = config('ctp.display_timezone');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['featuredMedia', 'category', 'author']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('thumb')->label('')
                    ->state(fn (Article $record) => $record->featuredMedia?->thumbUrl())
                    ->imageWidth(64)->imageHeight(40),
                TextColumn::make('title')->searchable()->sortable()->wrap()
                    ->description(fn (Article $record) => collect([
                        $record->type === ArticleType::Blog ? 'Blog' : $record->category?->name,
                        $record->isOutdated() ? '⚠ needs review' : null,
                    ])->filter()->implode(' · ')),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('author.name')->label('Author')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')->label('Published')->dateTime('j M Y, g:i a', $tz)->sortable()->placeholder('—'),
                TextColumn::make('views')->numeric()->sortable()->alignEnd(),
                TextColumn::make('updated_at')->label('Edited')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ArticleStatus::class),
                SelectFilter::make('type')->options(ArticleType::class),
                SelectFilter::make('category_id')->label('Category')->options(fn () => Category::treeOptions()),
                SelectFilter::make('tags')->relationship('tags', 'name')->multiple()->searchable()->preload(),
                SelectFilter::make('author_id')->label('Author')->relationship('author', 'name'),
                Filter::make('needs_review')->label('Needs review')
                    ->query(fn (Builder $query) => $query->where(fn (Builder $q) => $q
                        ->where('needs_review', true)
                        ->orWhere(fn (Builder $q) => $q->whereNotNull('published_at')
                            ->whereRaw('COALESCE(content_updated_at, published_at) < ?', [now()->subMonths((int) config('ctp.freshness_months'))])))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')->label('View')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (Article $record) => url($record->publicPath() ?? '/'), shouldOpenInNewTab: true)
                    ->visible(fn (Article $record) => $record->isPubliclyVisible()),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label('Publish')->icon(Heroicon::OutlinedGlobeAlt)->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => self::setStatus($records, ArticleStatus::Published))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')->label('Move to draft')->icon(Heroicon::OutlinedPencilSquare)->color('gray')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => self::setStatus($records, ArticleStatus::Draft))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @param Collection<int, Model> $records */
    private static function setStatus(Collection $records, ArticleStatus $status): void
    {
        foreach ($records as $article) {
            if ($article instanceof Article) {
                $article->update(['status' => $status]); // saving() derives Scheduled from a future date
            }
        }
    }
}
