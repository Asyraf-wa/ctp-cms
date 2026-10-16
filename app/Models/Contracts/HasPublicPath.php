<?php

namespace App\Models\Contracts;

// A model with its own public URL (pages, tutorials, authors…). Used by menus, sitemaps and redirects.
interface HasPublicPath
{
    public function pathForSlug(string $slug): ?string;

    public function publicPath(): ?string;

    public function isPubliclyVisible(): bool;
}
