<?php

namespace App\Ai;

use App\Settings\IntegrationSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;

/**
 * Key takeaways (TL;DR), the excerpt and the search snippet of an article, written by DeepSeek's chat API. The admin presses a button in the editor;
 * the title and the prose of the article are sent, nothing else. The result is only a suggestion to edit.
 */
class DeepSeek
{
    public const ENDPOINT = 'https://api.deepseek.com/chat/completions';

    public const DEFAULT_MODEL = 'deepseek-chat';

    // About 3,000 tokens of article text: enough to summarise, cheap, and well inside the model's context.
    public const MAX_CHARS = 12000;

    public const MAX_POINTS = 6;

    public const MAX_POINT_LENGTH = 200; // the TL;DR field's own limit

    public const MAX_EXCERPT_LENGTH = 500; // the excerpt column's own limit

    public const MAX_SEO_TITLE_LENGTH = 70; // the SEO title field's own limit

    public const MAX_SEO_DESCRIPTION_LENGTH = 320; // the meta description field's own limit

    public const MAX_KEYWORDS = 8;

    public function __construct(private IntegrationSettings $settings) {}

    public function configured(): bool
    {
        return filled($this->settings->deepseek_api_key);
    }

    /**
     * @return list<string> 3 to 6 short, factual points
     *
     * @throws AiException
     */
    public function keyTakeaways(string $title, string $text): array
    {
        return $this->parse($this->ask($this->system(), $title, $text, 600));
    }

    /**
     * @return string one or two plain sentences for cards, search results and the meta description
     *
     * @throws AiException
     */
    public function excerpt(string $title, string $text): string
    {
        $data = $this->decode($this->ask($this->excerptSystem(), $title, $text, 300));
        $excerpt = is_string($data['excerpt'] ?? null) ? trim(preg_replace('/\s+/u', ' ', strip_tags($data['excerpt'])) ?? '') : '';

        if ($excerpt === '') {
            throw new AiException('DeepSeek did not return an excerpt. Try again.');
        }

        return Str::limit($excerpt, self::MAX_EXCERPT_LENGTH, '');
    }

    /**
     * @return array{title: string, description: string, keywords: string}
     *
     * @throws AiException
     */
    public function seo(string $title, string $text): array
    {
        $data = $this->decode($this->ask($this->seoSystem(), $title, $text, 400));
        $clean = fn (mixed $v): string => is_string($v) ? trim(preg_replace('/\s+/u', ' ', strip_tags($v)) ?? '') : '';

        $seoTitle = Str::limit($clean($data['title'] ?? null), self::MAX_SEO_TITLE_LENGTH, '');
        $description = Str::limit($clean($data['description'] ?? null), self::MAX_SEO_DESCRIPTION_LENGTH, '');

        if ($seoTitle === '' || $description === '') {
            throw new AiException('DeepSeek did not return a title and description. Try again.');
        }

        $keywords = collect(is_array($data['keywords'] ?? null) ? $data['keywords'] : [])
            ->map($clean)
            ->filter()
            ->unique(fn (string $k) => mb_strtolower($k))
            ->take(self::MAX_KEYWORDS)
            ->implode(', ');

        return ['title' => $seoTitle, 'description' => $description, 'keywords' => $keywords];
    }

    private function seoSystem(): string
    {
        return <<<'PROMPT'
            You write the search-engine snippet of a programming tutorial. Answer in English, whatever the article's language.
            Return JSON only, in exactly this shape: {"title": "...", "description": "...", "keywords": ["...", "..."]}
            Rules:
            - title: at most 60 characters, the main topic first, no site name, no quotes around it.
            - description: 120 to 160 characters, says what the reader will learn or build and invites the click. Only what the article states.
            - keywords: 5 to 8 short phrases a reader would search for, lower case, no duplicates.
            - No marketing words, no markdown, no emoji. Keep code, file names and commands exactly as written.
            PROMPT;
    }

    /** @throws AiException */
    private function ask(string $system, string $title, string $text, int $maxTokens): string
    {
        if (! $this->configured()) {
            throw new AiException('Add your DeepSeek API key in Settings → Integrations first.');
        }

        $text = trim($text);
        if (mb_strlen($text) < 200) {
            throw new AiException('Write a little more of the article first: there is not enough text to summarise yet.');
        }

        try {
            $response = Http::withToken((string) $this->settings->deepseek_api_key)
                ->acceptJson()->asJson()->timeout(45)
                ->post(self::ENDPOINT, [
                    'model' => $this->settings->deepseek_model ?: self::DEFAULT_MODEL,
                    'temperature' => 0.3,
                    'max_tokens' => $maxTokens,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => "Title: {$title}\n\nArticle:\n".Str::limit($text, self::MAX_CHARS, '')],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new AiException('DeepSeek did not answer in time. Try again in a moment.');
        }

        if ($response->failed()) {
            throw new AiException($this->httpError($response));
        }

        return (string) $response->json('choices.0.message.content');
    }

    private function excerptSystem(): string
    {
        return <<<'PROMPT'
            You write the short summary ("excerpt") of a programming tutorial, shown on cards and in search results. Answer in English, whatever the article's language.
            Return JSON only, in exactly this shape: {"excerpt": "..."}
            Rules:
            - One or two plain sentences, 120 to 160 characters in total.
            - Say what the reader will learn or build. Only what the article states; nothing invented.
            - No marketing words, no "In this article", no markdown, no emoji.
            - Keep code, file names and commands exactly as written.
            PROMPT;
    }

    private function system(): string
    {
        return <<<'PROMPT'
            You write the "Key takeaways" box at the top of a programming tutorial. Answer in English, whatever the article's language.
            Return JSON only, in exactly this shape: {"takeaways": ["...", "..."]}
            Rules:
            - 3 to 5 points; each one is a single plain sentence of at most 160 characters.
            - Only facts the article actually states: what it teaches, the key commands, settings or concepts, and the outcome. Nothing invented.
            - No marketing words, no "In this article", no numbering, no markdown, no emoji.
            - Keep code, file names and commands exactly as written.
            PROMPT;
    }

    private function httpError(Response $response): string
    {
        return match ($response->status()) {
            401 => 'DeepSeek rejected the API key. Check it in Settings → Integrations.',
            402 => 'The DeepSeek account has no balance left. Top it up at platform.deepseek.com.',
            429 => 'DeepSeek is rate limiting requests. Wait a moment and try again.',
            500, 502, 503, 504 => 'DeepSeek is having trouble right now. Try again later.',
            default => 'DeepSeek returned an error (HTTP '.$response->status().').',
        };
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AiException
     */
    private function decode(string $content): array
    {
        try {
            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AiException('DeepSeek answered in an unexpected format. Try again.');
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @return list<string>
     *
     * @throws AiException
     */
    private function parse(string $content): array
    {
        $data = $this->decode($content);

        $items = is_array($data['takeaways'] ?? null) ? array_values($data['takeaways']) : [];

        $points = collect($items)
            ->filter(fn (mixed $p): bool => is_string($p))
            ->map(fn (string $p) => Str::limit(trim(preg_replace('/^\s*(?:[-*•]|\d+[.)])\s*/u', '', strip_tags($p)) ?? ''), self::MAX_POINT_LENGTH))
            ->filter()
            ->unique()
            ->take(self::MAX_POINTS)
            ->values()
            ->all();

        if ($points === []) {
            throw new AiException('DeepSeek did not return any takeaways. Try again.');
        }

        return $points;
    }
}
