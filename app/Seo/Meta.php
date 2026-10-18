<?php

namespace App\Seo;

use Carbon\CarbonInterface;

/**
 * Everything the <head> needs for one page (SEO-01): title, description, canonical, robots,
 * Open Graph / Twitter, breadcrumbs and JSON-LD nodes (SEO-04). Built by App\Seo\Seo.
 */
final class Meta
{
    /**
     * @param  list<array{0: string, 1: ?string}>  $breadcrumbs  [label, url]; also rendered on the page
     * @param  list<array<string, mixed>>  $schema  page-specific JSON-LD nodes, merged into the site @graph
     * @param  list<string>  $tags
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public string $type = 'website',
        public ?string $image = null,
        public ?int $imageWidth = null,
        public ?int $imageHeight = null,
        public ?string $imageAlt = null,
        public ?CarbonInterface $publishedAt = null,
        public ?CarbonInterface $modifiedAt = null,
        public ?string $author = null,
        public ?string $section = null,
        public array $tags = [],
        public array $breadcrumbs = [],
        public array $schema = [],
        public ?string $markdownUrl = null,
        public ?string $prevUrl = null,
        public ?string $nextUrl = null,
    ) {}
}
