<?php

namespace App\Legacy;

use Illuminate\Support\Str;

// MIG-06: counts, warnings and the review checklist, as Markdown in storage/logs.
class ImportReport
{
    /** @var array<string, array<string, int>> */
    public array $counts = [];

    /** @var array<string, int> */
    public array $totals = [];

    /** @var list<array<string, mixed>> */
    public array $articles = [];

    /** @var list<string> */
    public array $skipped = [];

    /** @var list<string> */
    public array $notes = [];

    /** @var list<string> */
    public array $errors = [];

    public function __construct(public bool $dryRun) {}

    public function count(string $group, string $what): void
    {
        $this->counts[$group][$what] = ($this->counts[$group][$what] ?? 0) + 1;
    }

    public function toMarkdown(): string
    {
        $lines = ['# Legacy import report', '', '- Run: '.now()->timezone(config('ctp.display_timezone'))->format('j M Y, g:i a').($this->dryRun ? ' (dry run: nothing was written)' : ''), ''];

        $lines[] = '## Counts';
        $lines[] = '';
        foreach ($this->counts as $group => $counts) {
            $lines[] = '- **'.Str::ucfirst($group).'**: '.collect($counts)->map(fn ($n, $what) => "{$n} {$what}")->join(', ');
        }
        foreach ($this->totals as $what => $n) {
            $lines[] = "- {$n} {$what}";
        }

        if ($this->errors) {
            array_push($lines, '', '## Errors', '', ...array_map(fn ($e) => '- '.$e, $this->errors));
        }

        $review = array_values(array_filter($this->articles, fn ($a) => ($a['review'] ?? false) || ! empty($a['notes'])));
        $lines[] = '';
        $lines[] = '## Review checklist ('.count($review).')';
        $lines[] = '';
        foreach ($review as $a) {
            $lines[] = '- [ ] ['.$a['title'].']('.$a['url'].')'.(empty($a['notes']) ? '' : ': '.implode('; ', $a['notes']));
        }

        $lines[] = '';
        $lines[] = '## All articles';
        $lines[] = '';
        $lines[] = '| Article | Status | Headings | Code |';
        $lines[] = '|---|---|---|---|';
        foreach ($this->articles as $a) {
            $lines[] = '| ['.str_replace('|', '\\|', $a['title']).']('.$a['url'].') | '.$a['status'].' | '.($a['headings'] ?? '–').' | '.($a['code'] ?? '–').' |';
        }

        if ($this->skipped) {
            array_push($lines, '', '## Skipped', '', ...array_map(fn ($s) => '- '.$s, $this->skipped));
        }
        if ($this->notes) {
            array_push($lines, '', '## Notes', '', ...array_map(fn ($n) => '- '.$n, $this->notes));
        }

        return implode("\n", $lines)."\n";
    }
}
