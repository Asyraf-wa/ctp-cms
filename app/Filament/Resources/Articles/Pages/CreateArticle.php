<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Content\Revisions;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function afterCreate(): void
    {
        /** @var Article $article */
        $article = $this->getRecord();
        app(Revisions::class)->record($article, auth()->user());
    }

    // Keep writing after the first save.
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
