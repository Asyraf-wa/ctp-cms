<?php

namespace App\Filament\Resources\Comments\Pages;

use App\Filament\Resources\Comments\CommentResource;
use App\Models\Comment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    public function getTabs(): array
    {
        $counts = Comment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(Comment::STATUSES)->mapWithKeys(fn (string $label, string $status) => [
            $status => Tab::make($label)
                ->badge($counts->get($status) ?: null)
                ->badgeColor($status === Comment::PENDING ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status)),
        ])->all();
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return Comment::where('status', Comment::PENDING)->exists() ? Comment::PENDING : Comment::APPROVED;
    }
}
