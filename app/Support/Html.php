<?php

namespace App\Support;

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
}
