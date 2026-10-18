<?php

namespace App\Content;

use Filament\Forms\Components\CodeEditor\Enums\Language as EditorLanguage;
use Tempest\Highlight\Highlighter;

/**
 * Server-side syntax highlighting (ARCH §5 step 4) and the one code-block markup used everywhere:
 * header (filename / language / copy), optional line numbers, highlighted lines as an overlay.
 */
class CodeHighlighter
{
    // key => [label, Filament CodeEditor language for editing (null = plain)]
    public const LANGUAGES = [
        'php' => ['PHP', EditorLanguage::Php],
        'blade' => ['Blade', EditorLanguage::Php],
        'html' => ['HTML', EditorLanguage::Html],
        'css' => ['CSS', EditorLanguage::Css],
        'scss' => ['SCSS', EditorLanguage::Css],
        'javascript' => ['JavaScript', EditorLanguage::JavaScript],
        'typescript' => ['TypeScript', EditorLanguage::JavaScript],
        'vue' => ['Vue', EditorLanguage::Html],
        'json' => ['JSON', EditorLanguage::Json],
        'sql' => ['SQL', EditorLanguage::Sql],
        'bash' => ['Bash', null],
        'terminal' => ['Terminal', null],
        'yaml' => ['YAML', EditorLanguage::Yaml],
        'xml' => ['XML', EditorLanguage::Xml],
        'markdown' => ['Markdown', EditorLanguage::Markdown],
        'python' => ['Python', EditorLanguage::Python],
        'dockerfile' => ['Dockerfile', null],
        'nginx' => ['Nginx', null],
        'apache' => ['Apache', null],
        'dotenv' => ['.env', null],
        'ini' => ['INI', null],
        'diff' => ['Diff', null],
        'twig' => ['Twig', EditorLanguage::Html],
        'text' => ['Plain text', null],
    ];

    // Legacy Prism / common aliases → our keys.
    private const ALIASES = [
        'markup' => 'html', 'js' => 'javascript', 'ts' => 'typescript', 'sh' => 'bash', 'shell' => 'bash',
        'zsh' => 'bash', 'console' => 'terminal', 'yml' => 'yaml', 'env' => 'dotenv', 'plaintext' => 'text',
        'none' => 'text', 'svg' => 'xml', 'md' => 'markdown', 'py' => 'python', 'docker' => 'dockerfile',
    ];

    public function __construct(private Highlighter $highlighter = new Highlighter) {}

    public static function normalize(?string $language): string
    {
        $language = strtolower(trim((string) $language));
        $language = self::ALIASES[$language] ?? $language;

        return array_key_exists($language, self::LANGUAGES) ? $language : 'text';
    }

    /** @return array<string, string> key => label, for selects */
    public static function options(): array
    {
        return array_map(fn (array $l) => $l[0], self::LANGUAGES);
    }

    public static function editorLanguage(?string $language): ?EditorLanguage
    {
        return self::LANGUAGES[self::normalize($language)][1];
    }

    public function render(string $code, ?string $language = null, ?string $filename = null, ?string $highlight = null, bool $lineNumbers = false): string
    {
        $language = self::normalize($language);
        $code = rtrim(str_replace(["\r\n", "\r"], "\n", $code), "\n");

        $highlighter = $lineNumbers ? $this->highlighter->withGutter() : $this->highlighter;
        $html = $language === 'text' ? e($code) : $highlighter->parse($code, $language);

        $overlay = collect(self::parseLineRanges($highlight, substr_count($code, "\n") + 1))
            ->map(fn (int $line) => '<span class="code-block__mark" style="--line: '.($line - 1).'" aria-hidden="true"></span>')
            ->implode('');

        $label = e(self::LANGUAGES[$language][0]);
        $file = filled($filename) ? '<span class="code-block__file">'.e($filename).'</span>' : '<span class="code-block__file code-block__file--lang">'.$label.'</span>';
        $lang = filled($filename) ? '<span class="code-block__lang">'.$label.'</span>' : '';
        $classes = 'code-block'.($lineNumbers ? ' code-block--numbered' : '');

        return <<<HTML
            <figure class="{$classes}" data-language="{$language}">
            <figcaption class="code-block__header">{$file}{$lang}<button type="button" class="code-block__copy" data-copy-code aria-label="Copy code">Copy</button></figcaption>
            <div class="code-block__body">{$overlay}<pre tabindex="0"><code class="language-{$language}">{$html}</code></pre></div>
            </figure>
            HTML;
    }

    /**
     * "2, 5-7" → [2, 5, 6, 7], limited to the lines that exist.
     *
     * @return list<int>
     */
    public static function parseLineRanges(?string $spec, int $maxLine): array
    {
        $lines = [];

        foreach (preg_split('/[\s,]+/', (string) $spec, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            if (preg_match('/^(\d+)(?:-(\d+))?$/', $part, $m)) {
                $from = (int) $m[1];
                $to = (int) ($m[2] ?? $m[1]);
                for ($i = min($from, $to); $i <= max($from, $to) && $i <= $maxLine; $i++) {
                    $lines[$i] = $i;
                }
            }
        }

        ksort($lines);

        return array_values(array_filter($lines, fn (int $l) => $l >= 1));
    }
}
