<?php

namespace App\Legacy;

// What HtmlCleaner produced for one article, with counts for the import report (MIG-06).
class CleanResult
{
    public string $html = '';

    public int $headings = 0;

    public int $codeBlocks = 0;

    public int $terminals = 0;

    public int $lists = 0;

    public int $tables = 0;

    public int $filenames = 0;

    public int $internalLinks = 0;

    /** @var list<string> legacy image src values, in order */
    public array $images = [];

    /** @var list<string> */
    public array $warnings = [];
}
