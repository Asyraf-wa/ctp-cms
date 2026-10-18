<?php

namespace App\Content;

use App\Models\Article;
use App\Models\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * GEO-01, GEO-02: TipTap JSON → GitHub-flavoured Markdown, for /{slug}.md, llms-full.txt and
 * "Copy as Markdown". Works on the document (not the HTML), so code keeps its language,
 * callouts become labelled quotes and steps a numbered list.
 */
class MarkdownExporter
{
    /** @var Collection<int, Media> library media referenced by the document */
    private Collection $media;

    /** @param  array<string, mixed>|null  $doc */
    public function export(?array $doc): string
    {
        if (! $doc) {
            return '';
        }

        $ids = Article::mediaIdsIn($doc);
        $this->media = $ids ? Media::query()->whereIn('id', $ids)->get()->keyBy('id') : collect();

        $markdown = $this->blocks($doc['content'] ?? []);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $markdown))."\n";
    }

    /** @param  list<array<string, mixed>>  $nodes */
    private function blocks(array $nodes, string $indent = ''): string
    {
        return implode("\n\n", array_filter(array_map(fn (array $node) => $this->block($node, $indent), $nodes), fn ($b) => $b !== ''));
    }

    /** @param  array<string, mixed>  $node */
    private function block(array $node, string $indent = ''): string
    {
        $attrs = $node['attrs'] ?? [];
        $content = $node['content'] ?? [];

        return match ($node['type'] ?? null) {
            'paragraph' => $this->inline($content),
            'heading' => str_repeat('#', max(1, min(6, (int) ($attrs['level'] ?? 2)))).' '.$this->inline($content),
            'bulletList' => $this->listItems($content, ordered: false, indent: $indent),
            'orderedList' => $this->listItems($content, ordered: true, indent: $indent, start: (int) ($attrs['start'] ?? 1)),
            'blockquote' => $this->quote($this->blocks($content)),
            'horizontalRule' => '---',
            'codeBlock' => $this->fence($this->plain($content), $attrs['language'] ?? null),
            'image' => $this->image($attrs),
            'table' => $this->table($content),
            'details' => $this->details($content),
            'customBlock' => $this->customBlock((string) ($attrs['id'] ?? ''), $attrs['config'] ?? []),
            default => $content ? $this->blocks($content) : '',
        };
    }

    /** @param  list<array<string, mixed>>  $items */
    private function listItems(array $items, bool $ordered, string $indent, int $start = 1): string
    {
        $lines = [];
        foreach ($items as $i => $item) {
            $marker = $ordered ? ($start + $i).'. ' : '- ';
            $children = $item['content'] ?? [];
            $first = array_shift($children);
            $text = $first ? $this->block($first, $indent.'   ') : '';
            $rest = $children ? "\n".$this->blocks($children, $indent.'   ') : '';
            $body = $text.$rest;
            // Continuation lines line up under the item text.
            $lines[] = $indent.$marker.str_replace("\n", "\n".$indent.str_repeat(' ', strlen($marker)), $body);
        }

        return implode("\n", $lines);
    }

    /** @param  list<array<string, mixed>>  $nodes */
    private function inline(array $nodes): string
    {
        $out = '';
        foreach ($nodes as $node) {
            if (($node['type'] ?? null) === 'hardBreak') {
                $out .= "  \n";

                continue;
            }
            if (($node['type'] ?? null) === 'image') {
                $out .= $this->image($node['attrs'] ?? []);

                continue;
            }
            $text = (string) ($node['text'] ?? '');
            $marks = collect((array) ($node['marks'] ?? []))->keyBy('type');
            if ($marks->has('code')) {
                $text = $this->inlineCode($text);
            } else {
                $text = $this->escape($text);
                if ($marks->has('bold')) {
                    $text = "**{$text}**";
                }
                if ($marks->has('italic')) {
                    $text = "*{$text}*";
                }
                if ($marks->has('strike')) {
                    $text = "~~{$text}~~";
                }
            }
            if ($href = $marks->get('link')['attrs']['href'] ?? null) {
                $text = "[{$text}]({$href})";
            }
            $out .= $text;
        }

        return $out;
    }

    /** @param  list<array<string, mixed>>  $nodes */
    private function plain(array $nodes): string
    {
        return implode('', array_map(fn ($n) => ($n['type'] ?? null) === 'hardBreak' ? "\n" : (string) ($n['text'] ?? ''), $nodes));
    }

    /** @param  array<string, mixed>  $config */
    private function customBlock(string $id, array $config): string
    {
        return match ($id) {
            'code' => trim((filled($config['filename'] ?? null) ? '`'.$config['filename']."`\n\n" : '').$this->fence((string) ($config['code'] ?? ''), $config['language'] ?? null)),
            'terminal' => $this->fence(collect(preg_split('/\R/', (string) ($config['commands'] ?? '')) ?: [])->filter()->map(fn ($c) => '$ '.$c)->join("\n")
                .(filled($config['output'] ?? null) ? "\n".$config['output'] : ''), 'bash'),
            'callout' => $this->quote('**'.(filled($config['title'] ?? null) ? $config['title'] : Str::ucfirst((string) ($config['type'] ?? 'note'))).':** '.trim((string) ($config['body'] ?? ''))),
            'steps' => collect((array) ($config['steps'] ?? []))->values()->map(fn ($step, $i) => ($i + 1).'. **'.trim((string) ($step['title'] ?? '')).'**'
                .(filled($step['body'] ?? null) ? "\n   ".str_replace("\n", "\n   ", trim((string) $step['body'])) : ''))->join("\n"),
            'code_tabs' => collect((array) ($config['tabs'] ?? []))->map(fn ($tab) => '**'.($tab['label'] ?? $tab['filename'] ?? 'Code').'**'."\n\n".$this->fence((string) ($tab['code'] ?? ''), $tab['language'] ?? null))->join("\n\n"),
            'image' => $this->libraryImage($config['media_id'] ?? null, $config['alt'] ?? null, $config['caption'] ?? null),
            'youtube' => filled($config['url'] ?? null) ? '[▶ '.($config['title'] ?? 'Video').']('.$config['url'].')' : '',
            'codepen' => filled($config['url'] ?? null) ? '[CodePen: '.($config['title'] ?? 'demo').']('.$config['url'].')' : '',
            default => '',
        };
    }

    /** @param  array<string, mixed>  $attrs */
    private function image(array $attrs): string
    {
        if (is_numeric($attrs['id'] ?? null) && ($media = $this->media->get((int) $attrs['id']))) {
            return $this->libraryImage($media->id, $attrs['alt'] ?? null, null);
        }

        return filled($attrs['src'] ?? null) ? '!['.$this->escape((string) ($attrs['alt'] ?? '')).']('.$attrs['src'].')' : '';
    }

    private function libraryImage(mixed $id, ?string $alt, ?string $caption): string
    {
        $media = is_numeric($id) ? $this->media->get((int) $id) : null;
        if (! $media) {
            return '';
        }
        $markdown = '!['.$this->escape((string) ($alt ?: $media->alt)).']('.$media->url().')';

        return filled($caption) ? $markdown."\n*".$this->escape((string) $caption).'*' : $markdown;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function table(array $rows): string
    {
        $cells = array_map(fn ($row) => array_map(fn ($cell) => str_replace(['|', "\n"], ['\\|', ' '], trim($this->blocks($cell['content'] ?? []))), $row['content'] ?? []), $rows);
        if (! $cells) {
            return '';
        }
        $width = max(array_map('count', $cells));
        $line = fn (array $row) => '| '.implode(' | ', array_pad($row, $width, '')).' |';
        $out = [$line($cells[0]), '| '.implode(' | ', array_fill(0, $width, '---')).' |'];
        foreach (array_slice($cells, 1) as $row) {
            $out[] = $line($row);
        }

        return implode("\n", $out);
    }

    /** @param  list<array<string, mixed>>  $content */
    private function details(array $content): string
    {
        $summary = collect($content)->firstWhere('type', 'detailsSummary');
        $body = collect($content)->firstWhere('type', 'detailsContent');

        return "<details>\n<summary>".e($this->inline($summary['content'] ?? []))."</summary>\n\n".$this->blocks($body['content'] ?? [])."\n\n</details>";
    }

    private function fence(string $code, ?string $language): string
    {
        // A fence longer than any backtick run inside the code.
        preg_match_all('/`{3,}/', $code, $runs);
        $ticks = str_repeat('`', max(3, ...array_map(fn ($r) => strlen($r) + 1, $runs[0] ?: ['``'])));

        return $ticks.($language && $language !== 'plaintext' ? $language : '')."\n".rtrim($code, "\n")."\n".$ticks;
    }

    private function inlineCode(string $text): string
    {
        $ticks = str_contains($text, '`') ? '``' : '`';

        return $ticks.($ticks === '``' ? ' '.$text.' ' : $text).$ticks;
    }

    private function quote(string $text): string
    {
        return '> '.str_replace("\n", "\n> ", $text);
    }

    // Escape characters that would otherwise start Markdown syntax in plain text.
    private function escape(string $text): string
    {
        return (string) preg_replace('/([\\\\`*_\[\]<>])/', '\\\\$1', $text);
    }
}
