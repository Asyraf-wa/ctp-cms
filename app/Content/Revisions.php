<?php

namespace App\Content;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

// PRD ART-03: a revision on every save (last 20 kept) and one rolling autosave per article and user.
class Revisions
{
    public function record(Article $article, ?User $user): ArticleRevision
    {
        $revision = $article->revisions()->create([
            'user_id' => $user?->id,
            ...$article->only(['title', 'excerpt', 'tldr', 'body']),
            'is_autosave' => false,
        ]);

        $keep = $article->revisions()->where('is_autosave', false)->limit(ArticleRevision::KEEP)->pluck('id');
        $article->revisions()->where('is_autosave', false)->whereNotIn('id', $keep)->delete();

        // A manual save supersedes the editor's autosave.
        $article->revisions()->where('is_autosave', true)->where('user_id', $user?->id)->delete();

        return $revision;
    }

    /** @param array{title?: mixed, excerpt?: mixed, tldr?: mixed, body?: mixed} $state the editor's unsaved state */
    public function autosave(Article $article, User $user, array $state): ?ArticleRevision
    {
        $fields = [
            'title' => (string) ($state['title'] ?? $article->title),
            'excerpt' => $state['excerpt'] ?? null,
            'tldr' => self::plainList($state['tldr'] ?? []),
            'body' => is_array($state['body'] ?? null) ? $state['body'] : $article->body,
        ];

        $unchanged = $fields['title'] === $article->title
            && $fields['excerpt'] === $article->excerpt
            && $fields['tldr'] === ($article->tldr ?? [])
            && $fields['body'] == $article->body;

        if ($unchanged) {
            return null;
        }

        return ArticleRevision::updateOrCreate(
            ['article_id' => $article->id, 'user_id' => $user->id, 'is_autosave' => true],
            $fields,
        );
    }

    // An autosave newer than the last real save means unsaved work from an earlier session.
    public function pendingAutosave(Article $article, User $user): ?ArticleRevision
    {
        return $article->revisions()
            ->where('is_autosave', true)
            ->where('user_id', $user->id)
            ->where('updated_at', '>', $article->updated_at)
            ->first();
    }

    /**
     * Form state for a simple repeater is [uuid => ['point' => …]]; revisions store a plain list like the article.
     *
     * @return list<string>
     */
    private static function plainList(mixed $items): array
    {
        return collect((array) $items)
            ->map(fn ($item) => is_array($item) ? (string) (reset($item) ?: '') : (string) $item)
            ->filter(fn (string $item) => $item !== '')
            ->values()
            ->all();
    }

    /** @param array<string, mixed>|null $body */
    public static function text(?string $title, ?array $body): string
    {
        $text = $body ? RichContentRenderer::make($body)->customBlocks(Article::BLOCKS)->toText() : '';

        return trim(($title ?? '')."\n\n".$text);
    }
}
