<?php

namespace App\Support;

// The commit the app folder is on, read from .git without running git (cPanel's clone IS the live app).
class Release
{
    public static function id(?string $root = null): ?string
    {
        $git = ($root ?? base_path()).'/.git';
        $head = @file_get_contents($git.'/HEAD');

        if ($head === false) {
            return null;
        }

        $head = trim($head);

        if (! str_starts_with($head, 'ref: ')) {
            return $head;
        }

        $ref = substr($head, 5);
        $sha = @file_get_contents($git.'/'.$ref);

        if ($sha !== false) {
            return trim($sha);
        }

        foreach (@file($git.'/packed-refs', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (str_ends_with($line, ' '.$ref)) {
                return explode(' ', $line)[0];
            }
        }

        return null;
    }
}
