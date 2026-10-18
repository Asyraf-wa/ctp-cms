<?php

namespace App\Support;

// PRD MENU-06: highlight the menu item for the current page.
class Navigation
{
    public static function isActive(?string $href): bool
    {
        if ($href === null || ! str_starts_with($href, url('/'))) {
            return false;
        }

        $path = '/'.trim((string) parse_url($href, PHP_URL_PATH), '/');
        $current = '/'.trim(request()->path(), '/');

        return $path === '/' ? $current === '/' : ($current === $path || str_starts_with($current, $path.'/'));
    }
}
