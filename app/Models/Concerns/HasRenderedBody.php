<?php

namespace App\Models\Concerns;

use App\Content\ArticleRenderer;
use App\Content\Blocks;
use App\Media\MediaLibraryAttachmentProvider;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;

/**
 * A TipTap JSON `body` edited with the shared editor (blocks + media library) and rendered on save
 * through ArticleRenderer, so articles, pages and projects share one pipeline (ARCH §5).
 */
trait HasRenderedBody
{
    use InteractsWithRichContent;

    /** @var list<int> media ids found in the body by the last render */
    protected array $bodyMediaIds = [];

    public const BLOCKS = [
        Blocks\CodeBlock::class,
        Blocks\ImageBlock::class,
        Blocks\CalloutBlock::class,
        Blocks\CodeTabsBlock::class,
        Blocks\TerminalBlock::class,
        Blocks\StepsBlock::class,
        Blocks\YouTubeBlock::class,
        Blocks\CodePenBlock::class,
    ];

    public function setUpRichContent(): void
    {
        $this->registerRichContent('body')
            ->json()
            ->customBlocks(self::BLOCKS)
            ->fileAttachmentProvider(new MediaLibraryAttachmentProvider);
    }

    /** @return array{html: string, text: string, toc: list<array{id: string, label: string, level: int}>, words: int, minutes: int, media: list<int>} */
    protected function renderRichBody(): array
    {
        $html = $this->body ? $this->getRichContentAttribute('body')->getRenderer()->toUnsafeHtml() : '';
        $result = app(ArticleRenderer::class)->render($html);
        $this->bodyMediaIds = $result['media'];

        return $result;
    }

    /** @return list<int> */
    protected function bodyMediaIds(): array
    {
        if (! $this->bodyMediaIds && $this->body) {
            $this->bodyMediaIds = self::mediaIdsIn($this->body);
        }

        return $this->bodyMediaIds;
    }

    /**
     * Media ids referenced in a TipTap document: editor attachments and Image blocks.
     *
     * @param  array<string, mixed>  $node
     * @return list<int>
     */
    public static function mediaIdsIn(array $node): array
    {
        $ids = [];
        if (($node['type'] ?? null) === 'image' && is_numeric($node['attrs']['id'] ?? null)) {
            $ids[] = (int) $node['attrs']['id'];
        }
        if (($node['type'] ?? null) === 'customBlock' && ($node['attrs']['id'] ?? null) === 'image' && is_numeric($node['attrs']['config']['media_id'] ?? null)) {
            $ids[] = (int) $node['attrs']['config']['media_id'];
        }
        foreach ($node['content'] ?? [] as $child) {
            $ids = [...$ids, ...self::mediaIdsIn($child)];
        }

        return array_values(array_unique($ids));
    }
}
