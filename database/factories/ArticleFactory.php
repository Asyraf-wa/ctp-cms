<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => ArticleType::Tutorial,
            'title' => rtrim(fake()->unique()->sentence(5), '.'),
            'excerpt' => fake()->sentence(15),
            'body' => self::doc([['paragraph', fake()->paragraph()]]),
            'status' => ArticleStatus::Draft,
        ];
    }

    public function published(?\DateTimeInterface $at = null): static
    {
        return $this->state(['status' => ArticleStatus::Published, 'published_at' => $at ?? now()->subDay()]);
    }

    public function blog(): static
    {
        return $this->state(['type' => ArticleType::Blog]);
    }

    /**
     * A small TipTap document: [['paragraph', 'text'], ['heading', 'text', 2], ['codeBlock', 'code', 'php']].
     *
     * @param  list<array<int, mixed>>  $nodes
     * @return array<string, mixed>
     */
    public static function doc(array $nodes): array
    {
        return ['type' => 'doc', 'content' => array_map(fn (array $n) => match ($n[0]) {
            'heading' => ['type' => 'heading', 'attrs' => ['level' => $n[2] ?? 2], 'content' => [['type' => 'text', 'text' => $n[1]]]],
            'codeBlock' => ['type' => 'codeBlock', 'attrs' => ['language' => $n[2] ?? null], 'content' => [['type' => 'text', 'text' => $n[1]]]],
            'block' => ['type' => 'customBlock', 'attrs' => ['id' => $n[1], 'config' => $n[2]]],
            'image' => ['type' => 'paragraph', 'content' => [['type' => 'image', 'attrs' => ['id' => $n[1], 'src' => 'x']]]],
            default => ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $n[1]]]],
        }, $nodes)];
    }
}
