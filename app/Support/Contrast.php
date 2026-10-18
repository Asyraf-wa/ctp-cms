<?php

namespace App\Support;

// WCAG 2.x relative luminance, used to pick readable text on a user-chosen colour.
class Contrast
{
    public const LIGHT = '#ffffff';

    public const DARK = '#0f172a';

    public static function readableOn(string $hex): string
    {
        $bg = self::luminance($hex);

        return self::ratio($bg, self::luminance(self::LIGHT)) >= self::ratio($bg, self::luminance(self::DARK))
            ? self::LIGHT
            : self::DARK;
    }

    public static function ratio(float $a, float $b): float
    {
        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    public static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        [$r, $g, $b] = array_map(function (string $pair): float {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 0, 6), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
