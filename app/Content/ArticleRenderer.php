<?php

namespace App\Content;

use App\Models\Media;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Str;

/**
 * ARCH §5: editor JSON → final article HTML, run once on save.
 * TipTap JSON → HTML (custom blocks included) → H1→H2 → heading ids + TOC → code highlighting
 * → responsive images → table wrappers → link hygiene → text, word count, reading time.
 */
class ArticleRenderer
{
    public function __construct(private CodeHighlighter $highlighter) {}

    /** @return array{html: string, text: string, toc: list<array{id: string, label: string, level: int}>, words: int, minutes: int, media: list<int>} */
    public function render(string $html): array
    {
        $doc = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR);
        $body = $doc->body;

        $this->promoteH1($doc);
        $this->closeHeadingGaps($doc);
        $toc = $this->headings($doc);
        $codeBlocks = $this->highlightCode($doc);
        $media = $this->images($doc);
        $this->wrapTables($doc);
        $this->links($doc);

        $codeText = collect($body->querySelectorAll('.code-block, .terminal'))->map(fn (Element $e) => $e->textContent)->implode(' ');
        $text = $this->plainText($body);
        $proseWords = max(0, str_word_count($text) - str_word_count($codeText));

        return [
            'html' => $body->innerHTML,
            'text' => $text,
            'toc' => $toc,
            'words' => $proseWords,
            'minutes' => max(1, (int) ceil($proseWords / 220 + $codeBlocks * 0.25)),
            'media' => $media,
        ];
    }

    // The page title is the only H1 (SEO-09).
    private function promoteH1(HTMLDocument $doc): void
    {
        foreach ($doc->body->querySelectorAll('h1') as $h1) {
            $this->rename($doc, $h1, 'h2');
        }
    }

    // H2 → H4 becomes H2 → H3: a heading may go at most one level deeper than the one before it (the title is the H1).
    private function closeHeadingGaps(HTMLDocument $doc): void
    {
        $previous = 1;
        foreach ($doc->body->querySelectorAll('h2, h3, h4, h5, h6') as $heading) {
            $level = (int) substr($heading->tagName, 1);
            if ($level > $previous + 1) {
                $level = $previous + 1;
                $this->rename($doc, $heading, 'h'.$level);
            }
            $previous = $level;
        }
    }

    /** @return list<array{id: string, label: string, level: int}> */
    private function headings(HTMLDocument $doc): array
    {
        $toc = [];
        $used = [];

        foreach ($doc->body->querySelectorAll('h2, h3, h4') as $heading) {
            $label = trim(preg_replace('/\s+/', ' ', $heading->textContent));
            if ($label === '') {
                continue;
            }

            $base = Str::slug($label) ?: 'section';
            $id = $base;
            for ($i = 2; isset($used[$id]); $i++) {
                $id = "{$base}-{$i}";
            }
            $used[$id] = true;
            $heading->setAttribute('id', $id);

            $level = (int) substr($heading->tagName, 1);
            if ($level <= 3) {
                $toc[] = ['id' => $id, 'label' => $label, 'level' => $level];
            }
        }

        return $toc;
    }

    // Native editor code blocks (quick pastes) get the same treatment as the Code block.
    private function highlightCode(HTMLDocument $doc): int
    {
        foreach ($doc->body->querySelectorAll('pre') as $pre) {
            if ($pre->closest('.code-block, .terminal')) {
                continue;
            }

            $code = $pre->querySelector('code') ?? $pre;
            preg_match('/language-([\w+-]+)/', (string) $code->getAttribute('class'), $m);

            $this->replaceWithHtml($doc, $pre, $this->highlighter->render($code->textContent, $m[1] ?? null));
        }

        return $doc->body->querySelectorAll('.code-block, .terminal')->length;
    }

    /**
     * Library images (editor attachments carry data-id, Image blocks data-media-id) → responsive figures.
     *
     * @return list<int> media ids used
     */
    private function images(HTMLDocument $doc): array
    {
        $images = collect($doc->body->querySelectorAll('img[data-id], img[data-media-id]'));
        $ids = $images->map(fn (Element $img) => (int) ($img->getAttribute('data-media-id') ?: $img->getAttribute('data-id')))->filter()->unique();
        $library = Media::whereIn('id', $ids)->get()->keyBy('id');

        foreach ($images as $img) {
            $media = $library->get((int) ($img->getAttribute('data-media-id') ?: $img->getAttribute('data-id')));

            if ($media === null) {
                $img->remove();

                continue;
            }

            $alt = $img->getAttribute('alt') ?: (string) $media->alt;
            $caption = $img->getAttribute('data-caption') ?: (string) $media->caption;
            $size = $img->getAttribute('data-size') ?: 'normal';
            $link = $img->getAttribute('data-link');

            $tag = '<img src="'.e($media->url($media->isRaster() ? '1280' : null)).'"'
                .($media->srcset() ? ' srcset="'.e($media->srcset()).'" sizes="(min-width: 1024px) 720px, 100vw"' : '')
                .($media->width ? ' width="'.$media->width.'" height="'.$media->height.'"' : '')
                .' alt="'.e($alt).'" loading="lazy" decoding="async">';
            $tag = $link ? '<a href="'.e($link).'">'.$tag.'</a>' : $tag;
            $figure = '<figure class="article-figure article-figure--'.e($size).'">'.$tag
                .($caption !== '' ? '<figcaption>'.e($caption).($media->credit ? ' <span class="article-figure__credit">'.e($media->credit).'</span>' : '').'</figcaption>' : '')
                .'</figure>';

            // Image blocks are always figures. Editor images become figures when alone in their paragraph,
            // otherwise they stay inline.
            $parent = $img->parentElement;
            if ($img->hasAttribute('data-media-id')) {
                $this->replaceWithHtml($doc, $img, $figure);
            } elseif ($parent && $parent->tagName === 'P' && trim($parent->textContent) === '' && $parent->querySelectorAll('img')->length === 1) {
                $this->replaceWithHtml($doc, $parent, $figure);
            } else {
                $this->replaceWithHtml($doc, $img, $tag);
            }
        }

        return $library->keys()->map(fn ($id) => (int) $id)->values()->all();
    }

    // Wide tables scroll inside the article instead of the page (mobile).
    private function wrapTables(HTMLDocument $doc): void
    {
        foreach ($doc->body->querySelectorAll('table') as $table) {
            if ($table->parentElement?->classList->contains('table-wrap')) {
                continue;
            }
            $wrap = $doc->createElement('div');
            $wrap->setAttribute('class', 'table-wrap');
            $table->before($wrap);
            $wrap->append($table);
        }
    }

    private function links(HTMLDocument $doc): void
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        foreach ($doc->body->querySelectorAll('a[href]') as $a) {
            $href = trim($a->getAttribute('href'));

            if (preg_match('/^\s*(javascript|data|vbscript):/i', $href)) {
                $a->removeAttribute('href');

                continue;
            }

            $linkHost = parse_url($href, PHP_URL_HOST);
            if ($linkHost && $linkHost !== $host && ! $a->closest('.code-block, .terminal, .video-embed, .pen-embed')) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener');
            }
        }
    }

    private function plainText(Element $body): string
    {
        $html = preg_replace('#</(p|h[1-6]|li|pre|figure|figcaption|div|tr|blockquote|aside)>#i', "$0\n", $body->innerHTML);
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace("/[ \t]+/", ' ', preg_replace("/\n\s*\n+/", "\n", $text)));
    }

    private function rename(HTMLDocument $doc, Element $element, string $tag): void
    {
        $new = $doc->createElement($tag);
        foreach ($element->attributes as $attribute) {
            $new->setAttribute($attribute->name, $attribute->value);
        }
        while ($element->firstChild) {
            $new->append($element->firstChild);
        }
        $element->replaceWith($new);
    }

    private function replaceWithHtml(HTMLDocument $doc, Element $element, string $html): void
    {
        $holder = $doc->createElement('div');
        $holder->innerHTML = $html;
        $element->replaceWith(...iterator_to_array($holder->childNodes));
    }
}
