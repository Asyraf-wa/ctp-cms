<?php

namespace App\Ai;

use App\Content\ArticleRenderer;
use App\Models\Article;

/** The plain prose of an article, as the editor currently holds it (TipTap JSON or HTML), for the AI to read. */
class ArticleText
{
    /** @param array<string, mixed>|string|null $body */
    public static function from(array|string|null $body): string
    {
        if (blank($body)) {
            return '';
        }

        if (is_string($body)) {
            return trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5));
        }

        $article = new Article;
        $article->body = $body;
        $html = $article->getRichContentAttribute('body')->getRenderer()->toUnsafeHtml();

        return trim(app(ArticleRenderer::class)->render($html)['text']);
    }
}
