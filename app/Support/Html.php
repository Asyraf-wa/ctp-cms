<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class Html
{
    // Short inline text such as the topbar notification: emphasis and safe links only.
    public static function inline(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $config = (new HtmlSanitizerConfig)
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('a', ['href', 'target', 'rel'])
            // Unlisted elements are dropped with their children; wrappers must only lose the tag.
            ->blockElement('p')
            ->blockElement('div')
            ->blockElement('span')
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener');

        // Paragraph wrappers from the editor become plain spaces: the notice is one line.
        $html = preg_replace('#</p>\s*<p[^>]*>#i', ' ', $html);

        return trim((new HtmlSanitizer($config))->sanitize($html));
    }

    // Short rich text such as the author's full bio: paragraphs, lists, emphasis and safe links.
    public static function basic(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('a', ['href'])
            ->blockElement('div')
            ->blockElement('span')
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener');

        return trim((new HtmlSanitizer($config))->sanitize($html));
    }

    /**
     * CMT-01: comments are Markdown-lite (emphasis, inline code, fences, links, lists, quotes).
     * Raw HTML is escaped before Markdown runs, then the output is sanitised again; links get nofollow ugc.
     */
    public static function comment(?string $text): string
    {
        if (blank($text)) {
            return '';
        }

        $html = Str::markdown((string) $text, ['html_input' => 'escape', 'allow_unsafe_links' => false, 'max_nesting_level' => 10]);

        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('del')
            ->allowElement('code', ['class'])
            ->allowElement('pre')
            ->allowElement('blockquote')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('a', ['href'])
            // Headings and images are not part of comment Markdown: keep their text only.
            ->blockElement('h1')->blockElement('h2')->blockElement('h3')->blockElement('h4')->blockElement('h5')->blockElement('h6')
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->forceAttribute('a', 'rel', 'nofollow ugc noopener')
            ->forceAttribute('a', 'target', '_blank');

        return trim((new HtmlSanitizer($config))->sanitize($html));
    }
}
