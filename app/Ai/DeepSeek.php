<?php

namespace App\Ai;

use App\Settings\IntegrationSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;

/**
 * Key takeaways (TL;DR) for an article, written by DeepSeek's chat API. The admin presses a button in the editor;
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
                    'max_tokens' => 600,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->system()],
                        ['role' => 'user', 'content' => "Title: {$title}\n\nArticle:\n".Str::limit($text, self::MAX_CHARS, '')],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new AiException('DeepSeek did not answer in time. Try again in a moment.');
        }

        if ($response->failed()) {
            throw new AiException($this->httpError($response));
        }

        return $this->parse((string) $response->json('choices.0.message.content'));
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
     * @return list<string>
     *
     * @throws AiException
     */
    private function parse(string $content): array
    {
        try {
            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AiException('DeepSeek answered in an unexpected format. Try again.');
        }

        $items = is_array($data) && is_array($data['takeaways'] ?? null) ? array_values($data['takeaways']) : [];

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
