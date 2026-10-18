<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New article'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'tutorials' => Tab::make('Tutorials')->modifyQueryUsing(fn (Builder $query) => $query->where('type', ArticleType::Tutorial)),
            'blog' => Tab::make('Blog')->modifyQueryUsing(fn (Builder $query) => $query->where('type', ArticleType::Blog)),
            'drafts' => Tab::make('Drafts')->modifyQueryUsing(fn (Builder $query) => $query->where('status', ArticleStatus::Draft)),
            'scheduled' => Tab::make('Scheduled')->modifyQueryUsing(fn (Builder $query) => $query->where('status', ArticleStatus::Scheduled)),
        ];
    }
}
