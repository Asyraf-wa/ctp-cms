<?php

namespace App\Legacy;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;
use Illuminate\Support\Str;

/**
 * MIG-04 (ARCH §10.3): legacy TinyMCE HTML → clean, editor-ready HTML.
 *
 * The legacy bodies have no headings, lay out text with <br> runs and Prism code blocks, and are
 * full of inline styles. This turns them into paragraphs, real H2/H3, lists, Code / Terminal /
 * CodePen custom blocks and image blocks, which the TipTap converter then reads.
 *
 * No IO here: images are handed to $resolveImage(src, alt) which returns block HTML (or null to drop).
 */
class HtmlCleaner
{
    private const HEADING_MAX = 80;

    private const SHELL = '#^(\$\s*)?(composer|bin[/\\\\]cake|cd|npm|npx|yarn|git|php|mkdir|sudo|apt(-get)?|mysql|mysqldump|cp|mv|ls|curl|wget|chmod|chown|ssh|scp|cake)\b#i';

    private const LANGUAGE_MAP = ['markup' => 'html', 'js' => 'javascript', 'java' => 'javascript', 'shell' => 'bash', 'sh' => 'bash'];

    /** @var list<string> */
    private array $blocks = [];

    /** @var list<array{type: string, html: string, n?: int}> lines waiting to be grouped */
    private array $lines = [];

    private ?string $pendingFilename = null;

    // Lines a filename stays valid for: "File Location: …" is often followed by one sentence, then the code.
    private int $filenameTtl = 0;

    private bool $has14pt = false;

    private CleanResult $result;

    /** @var (callable(string, string): ?string)|null */
    private $resolveImage;

    /** @param  (callable(string, string): ?string)|null  $resolveImage */
    public function clean(string $html, ?callable $resolveImage = null): CleanResult
    {
        $this->blocks = [];
        $this->lines = [];
        $this->pendingFilename = null;
        $this->result = new CleanResult;
        $this->resolveImage = $resolveImage;

        $html = str_replace(["\r\n", "\r"], "\n", $html);
        $this->has14pt = (bool) preg_match('/font-size:\s*14pt/i', $html);

        $doc = HTMLDocument::createFromString('<!DOCTYPE html><html><body><div id="root">'.$html.'</div></body></html>', LIBXML_NOERROR);
        $root = $doc->getElementById('root');

        if ($root) {
            foreach ($root->childNodes as $node) {
                $this->block($node);
            }
        }
        $this->flushLines();

        $this->result->html = implode("\n", $this->blocks);

        return $this->result;
    }

    // --- Blocks ---------------------------------------------------------------------------------

    private function block(Node $node): void
    {
        if ($node instanceof Text) {
            if (trim($this->text($node->textContent)) !== '') {
                $this->line('text', e($this->text($node->textContent)));
            }

            return;
        }
        if (! $node instanceof Element) {
            return;
        }

        match (strtolower($node->localName)) {
            'p' => $this->paragraph($node, separate: true),
            'div' => $this->div($node),
            'pre' => $this->pre($node),
            'ul', 'ol' => $this->listElement($node),
            'table' => $this->table($node),
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => $this->heading(trim($node->textContent), in_array($node->localName, ['h1', 'h2'], true) ? 2 : 3),
            'blockquote' => $this->emit('<blockquote><p>'.$this->inline($node).'</p></blockquote>'),
            'iframe' => $this->iframe($node),
            'hr' => $this->emit('<hr>'),
            'img' => $this->image($node),
            default => $this->unknown($node),
        };
    }

    // A <p>: its <br>-separated lines, then a paragraph break.
    private function paragraph(Element $p, bool $separate): void
    {
        $current = [];
        foreach ($p->childNodes as $child) {
            if ($child instanceof Element && $child->localName === 'br') {
                $this->classify($current);
                $current = [];

                continue;
            }
            if ($child instanceof Element && in_array($child->localName, ['iframe', 'pre', 'table', 'ul', 'ol'], true)) {
                $this->classify($current);
                $current = [];
                $this->block($child);

                continue;
            }
            $current[] = $child;
        }
        $this->classify($current);
        if ($separate) {
            $this->separator();
        }
    }

    // Some newer articles use one <div> per line, with <div>&nbsp;</div> as the gap.
    private function div(Element $div): void
    {
        $hasBlocks = false;
        foreach ($div->childNodes as $child) {
            if ($child instanceof Element && in_array($child->localName, ['p', 'div', 'pre', 'table', 'ul', 'ol', 'h1', 'h2', 'h3', 'h4'], true)) {
                $hasBlocks = true;
            }
        }
        if ($hasBlocks) {
            foreach ($div->childNodes as $child) {
                $this->block($child);
            }

            return;
        }
        if (trim($this->text($div->textContent)) === '' && ! $div->getElementsByTagName('img')->length) {
            $this->separator();

            return;
        }
        $this->paragraph($div, separate: false);
    }

    /** @param  list<Node>  $nodes  one visual line */
    private function classify(array $nodes): void
    {
        $text = trim($this->text(implode('', array_map(fn (Node $n) => $n->textContent, $nodes))));
        $images = [];
        foreach ($nodes as $n) {
            if ($n instanceof Element) {
                if ($n->localName === 'img') {
                    $images[] = $n;
                } else {
                    foreach ($n->getElementsByTagName('img') as $img) {
                        $images[] = $img;
                    }
                }
                foreach ($n->localName === 'iframe' ? [$n] : iterator_to_array($n->getElementsByTagName('iframe')) as $frame) {
                    $this->iframe($frame);
                }
            }
        }

        if ($text === '' && ! $images) {
            $this->separator();

            return;
        }
        if ($images && $text === '') {
            foreach ($images as $img) {
                $this->image($img);
            }

            return;
        }

        if (preg_match('/^(?:File\s+)?Location\s*:\s*(.+)$/i', $text, $m)) {
            $this->setFilename($m[1]);

            return;
        }
        if ($this->filenameTtl > 0 && --$this->filenameTtl === 0) {
            $this->pendingFilename = null;
        }
        // "…add the following code to ...\src\Application.php": the path names the next code block.
        // Paths use backslashes or slashes ("[\\\\/]" below is the regex class for either).
        if (preg_match('~(?:\.{2,}|…)?[\\\\/](?:[\w.-]+[\\\\/])*[\w.-]+\.(?:php|js|css|json|xml|html|env|ini|ctp|yml|yaml|sql|htaccess)\b~iu', $text, $m)) {
            $this->setFilename($m[0], countIt: false);
        }

        if ($level = $this->headingLevel($nodes, $text)) {
            $this->heading(rtrim($text, ': '), $level);

            return;
        }

        $html = trim(implode('', array_map(fn (Node $n) => $this->inlineNode($n), $nodes)));
        if (preg_match('/^[-–•]\s+/u', $text)) {
            $this->line('ul', (string) preg_replace('/^\s*(?:[-–•]|&ndash;)\s+/u', '', $html));
        } elseif (preg_match('/^(\d{1,2})[.)]\s+/', $text, $number)) {
            $this->line('ol', (string) preg_replace('/^\s*\d{1,2}[.)]\s+/', '', $html), (int) $number[1]);
        } else {
            $this->line('text', $html);
        }
        foreach ($images as $img) {
            $this->flushLines();
            $this->image($img);
        }
    }

    /**
     * A line is a heading when it is one bold run (optionally inside a 14pt span), short and
     * not a sentence. In articles that use 14pt headings, plain bold lines become H3.
     *
     * @param  list<Node>  $nodes
     */
    private function headingLevel(array $nodes, string $text): ?int
    {
        $meaningful = array_values(array_filter($nodes, fn (Node $n) => ! ($n instanceof Text && trim($this->text($n->textContent)) === '')));
        if (count($meaningful) !== 1 || ! $meaningful[0] instanceof Element) {
            return null;
        }
        $el = $meaningful[0];
        $isBold = in_array($el->localName, ['strong', 'b'], true) || ($el->localName === 'span' && $this->onlyChild($el, ['strong', 'b']));
        if (! $isBold || mb_strlen($text) > self::HEADING_MAX || preg_match('/[.!,;]$/', $text) || mb_strlen($text) < 3) {
            return null;
        }

        $style = strtolower($el->getAttribute('style') ?? '').' '.strtolower(implode(' ', array_map(fn (Element $e) => $e->getAttribute('style') ?? '', iterator_to_array($el->getElementsByTagName('span')))));
        $big = str_contains($style, '14pt');
        $this->result->headings++;

        return ($big || ! $this->has14pt) ? 2 : 3;
    }

    private function heading(string $text, int $level): void
    {
        $this->pendingFilename = null;
        $this->flushLines();
        $this->emit("<h{$level}>".e($text)."</h{$level}>");
    }

    private function pre(Element $pre): void
    {
        $this->flushLines();
        $code = $pre->getElementsByTagName('code')->item(0) ?? $pre;
        $classes = trim(($pre->getAttribute('class') ?? '').' '.($code->getAttribute('class') ?? ''));
        preg_match('/language-([a-z0-9]+)/i', $classes, $m);
        $language = strtolower($m[1] ?? 'text');
        $language = self::LANGUAGE_MAP[$language] ?? $language;
        $text = trim(str_replace("\u{a0}", ' ', $code->textContent), "\n");

        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== ''));
        $isShell = $lines && ! str_contains($text, '<?') && ! str_contains($text, ';') && collect($lines)->every(fn ($l) => preg_match(self::SHELL, $l));

        if ($isShell) {
            $this->customBlock('terminal', ['title' => $this->pendingFilename, 'commands' => implode("\n", array_map(fn ($l) => (string) preg_replace('/^\$\s*/', '', $l), $lines)), 'output' => null]);
            $this->result->terminals++;
        } else {
            $this->customBlock('code', [
                'language' => $language === 'text' ? 'text' : $language,
                'filename' => $this->pendingFilename,
                'highlight' => null,
                'code' => $text,
                'line_numbers' => str_contains($classes, 'line-numbers'),
            ]);
            $this->result->codeBlocks++;
        }
        $this->pendingFilename = null;
        $this->filenameTtl = 0;
    }

    private function listElement(Element $list): void
    {
        $this->flushLines();
        $tag = $list->localName === 'ol' ? 'ol' : 'ul';
        $items = [];
        foreach ($list->childNodes as $li) {
            if ($li instanceof Element && $li->localName === 'li') {
                $items[] = '<li><p>'.$this->inline($li).'</p></li>';
            }
        }
        if ($items) {
            $this->emit("<{$tag}>".implode('', $items)."</{$tag}>");
        }
    }

    // Structure only; the first row becomes the header when it is all bold.
    private function table(Element $table): void
    {
        $this->flushLines();
        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof Element && in_array($cell->localName, ['td', 'th'], true)) {
                    $cells[] = ['html' => $this->inline($cell), 'bold' => $cell->localName === 'th' || $this->onlyChild($cell, ['strong', 'b'])];
                }
            }
            if ($cells) {
                $rows[] = $cells;
            }
        }
        if (! $rows) {
            return;
        }
        $headerRow = collect($rows[0])->every(fn ($c) => $c['bold']);
        $html = '<table>';
        foreach ($rows as $i => $cells) {
            $tag = $i === 0 && $headerRow ? 'th' : 'td';
            $html .= '<tr>'.implode('', array_map(fn ($c) => "<{$tag}><p>{$c['html']}</p></{$tag}>", $cells)).'</tr>';
        }
        $this->emit($html.'</table>');
        $this->result->tables++;
    }

    private function iframe(Element $frame): void
    {
        $this->flushLines();
        $src = (string) $frame->getAttribute('src');
        if (preg_match('~codepen\.io/([\w-]+)/(?:pen|embed)/([\w-]+)~', $src, $m)) {
            $this->customBlock('codepen', ['url' => "https://codepen.io/{$m[1]}/pen/{$m[2]}", 'title' => $frame->getAttribute('title') ?: 'CodePen demo', 'tab' => 'result', 'height' => (int) ($frame->getAttribute('height') ?: 400)]);
        } elseif (preg_match('~(?:youtube(?:-nocookie)?\.com/embed/|youtu\.be/)([\w-]{11})~', $src, $m)) {
            $this->customBlock('youtube', ['url' => 'https://www.youtube.com/watch?v='.$m[1], 'title' => $frame->getAttribute('title') ?: 'Video', 'start' => null]);
        } else {
            $this->result->warnings[] = 'Removed an embed: '.Str::limit($src, 80);
        }
    }

    private function image(Element $img): void
    {
        $this->flushLines();
        $src = (string) $img->getAttribute('src');
        $alt = trim((string) $img->getAttribute('alt'));
        $this->result->images[] = $src;
        $html = $this->resolveImage ? ($this->resolveImage)($src, $alt) : null;
        if ($html) {
            $this->emit($html);
        }
    }

    // Demo forms, captcha widgets and the like: keep any readable text, report the element.
    private function unknown(Element $node): void
    {
        $this->result->warnings[] = 'Converted an unexpected <'.$node->localName.'> to text';
        $text = trim($this->text($node->textContent));
        if ($text !== '') {
            $this->line('text', e($text));
        }
    }

    // --- Output ---------------------------------------------------------------------------------

    private function line(string $type, string $html, ?int $number = null): void
    {
        if ($html === '') {
            return;
        }
        $last = end($this->lines);
        if ($last && $last['type'] !== $type) {
            $this->flushLines();
        }
        $this->lines[] = ['type' => $type, 'html' => $html, ...($number !== null ? ['n' => $number] : [])];
    }

    private function separator(): void
    {
        $this->flushLines();
    }

    // Turn the pending lines into one paragraph (joined by <br>) or one list.
    private function flushLines(): void
    {
        if (! $this->lines) {
            return;
        }
        $type = $this->lines[0]['type'];
        // A numbered list interrupted by text continues at its original number (3., 4. …).
        $start = ($this->lines[0]['n'] ?? 1) > 1 ? ' start="'.$this->lines[0]['n'].'"' : '';
        $parts = array_column($this->lines, 'html');
        $this->lines = [];

        if ($type === 'text') {
            $this->blocks[] = '<p>'.implode('<br>', $parts).'</p>';
        } else {
            $this->blocks[] = "<{$type}".($type === 'ol' ? $start : '').'>'.implode('', array_map(fn ($p) => "<li><p>{$p}</p></li>", $parts))."</{$type}>";
            $this->result->lists++;
        }
    }

    private function emit(string $html): void
    {
        $this->flushLines();
        $this->blocks[] = $html;
    }

    /** @param  array<string, mixed>  $config */
    private function customBlock(string $id, array $config): void
    {
        $this->emit('<div data-type="customBlock" data-id="'.$id.'" data-config="'.e((string) json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)).'"></div>');
    }

    // --- Inline -----------------------------------------------------------------------------------

    private function inline(Element $element): string
    {
        return trim(implode('', array_map(fn (Node $n) => $this->inlineNode($n), iterator_to_array($element->childNodes))));
    }

    // Emphasis, inline code and links survive; every style, class and wrapper span goes.
    private function inlineNode(Node $node): string
    {
        if ($node instanceof Text) {
            return e($this->text($node->textContent, collapse: true));
        }
        if (! $node instanceof Element) {
            return '';
        }
        $inner = implode('', array_map(fn (Node $n) => $this->inlineNode($n), iterator_to_array($node->childNodes)));

        return match (strtolower($node->localName)) {
            'strong', 'b' => trim($inner) === '' ? $inner : '<strong>'.$inner.'</strong>',
            'em', 'i' => trim($inner) === '' ? $inner : '<em>'.$inner.'</em>',
            'code' => '<code>'.e($node->textContent).'</code>',
            's', 'del', 'strike' => '<s>'.$inner.'</s>',
            'a' => $this->link($node, $inner),
            'br' => '<br>',
            'img', 'iframe', 'script', 'style' => '',
            default => $inner, // span, u, font, sup… unwrapped
        };
    }

    private function link(Element $a, string $inner): string
    {
        $href = trim((string) $a->getAttribute('href'));
        if ($href === '' || preg_match('/^\s*javascript:/i', $href)) {
            return $inner;
        }
        // Old internal links: /codethepixel/articles/{Slug} becomes the new lowercase root URL.
        if (preg_match('~^(?:https?://(?:www\.)?codethepixel\.com)?/(?:codethepixel/)?articles/(?:view/)?([\w-]+)/?$~i', $href, $m)) {
            $href = '/'.Str::lower($m[1]);
            $this->result->internalLinks++;
        }

        return '<a href="'.e($href).'">'.$inner.'</a>';
    }

    private function setFilename(string $raw, bool $countIt = true): void
    {
        $this->pendingFilename = $this->filename($raw);
        $this->filenameTtl = 3;
        if ($countIt) {
            $this->result->filenames++;
        }
    }

    // "...\src\Controller\UsersController.php" → "src/Controller/UsersController.php"
    private function filename(string $raw): string
    {
        $path = str_replace('\\', '/', trim($raw));
        $path = (string) preg_replace('~^(\.{2,}|…)/*~u', '', $path);

        return Str::limit(trim($path, ' /'), 120, '');
    }

    /** @param  list<string>  $names */
    private function onlyChild(Element $el, array $names): bool
    {
        $children = array_values(array_filter(iterator_to_array($el->childNodes), fn (Node $n) => ! ($n instanceof Text && trim($this->text($n->textContent)) === '')));

        return count($children) === 1 && $children[0] instanceof Element
            && (in_array($children[0]->localName, $names, true) || ($children[0]->localName === 'span' && $this->onlyChild($children[0], $names)));
    }

    private function text(string $text, bool $collapse = false): string
    {
        $text = str_replace("\u{a0}", ' ', $text);

        return $collapse ? (string) preg_replace('/\s+/u', ' ', $text) : $text;
    }
}
