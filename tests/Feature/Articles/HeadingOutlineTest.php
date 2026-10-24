<?php

use App\Content\ArticleRenderer;

// SEO health check: heading levels must not skip (H1 → H3). The page title is the only H1.
it('never skips a heading level', function (string $html, array $expected) {
    $out = app(ArticleRenderer::class)->render($html)['html'];

    preg_match_all('/<h([1-6])[ >]/', $out, $m);

    expect(array_map('intval', $m[1]))->toBe($expected);
})->with([
    'h3 straight after the title' => ['<h3>Problem</h3><p>x</p><h3>Fix</h3>', [2, 3]],
    'h4 under h2' => ['<h2>A</h2><h4>B</h4><h3>C</h3>', [2, 3, 3]],
    'a body H1 becomes H2' => ['<h1>A</h1><h2>B</h2>', [2, 2]],
    'a normal outline is untouched' => ['<h2>A</h2><h3>B</h3><h4>C</h4><h2>D</h2>', [2, 3, 4, 2]],
]);
