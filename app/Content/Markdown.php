<?php

namespace App\Content;

use Illuminate\Support\Str;

// Markdown-lite for block text fields (callouts, steps): emphasis, inline code, links, lists. Raw HTML is escaped.
class Markdown
{
    private const OPTIONS = ['html_input' => 'escape', 'allow_unsafe_links' => false];

    public static function block(?string $text): string
    {
        return blank($text) ? '' : trim(Str::markdown($text, self::OPTIONS));
    }

    public static function inline(?string $text): string
    {
        return blank($text) ? '' : trim(Str::inlineMarkdown($text, self::OPTIONS));
    }
}
