<?php

use App\Support\Contrast;

it('picks readable text for a background colour', function (string $bg, string $expected) {
    expect(Contrast::readableOn($bg))->toBe($expected);
})->with([
    'brand teal' => ['#0f766e', Contrast::LIGHT],
    'red' => ['#b91c1c', Contrast::LIGHT],
    'yellow' => ['#facc15', Contrast::DARK],
    'white' => ['#ffffff', Contrast::DARK],
    'short hex' => ['#000', Contrast::LIGHT],
]);
