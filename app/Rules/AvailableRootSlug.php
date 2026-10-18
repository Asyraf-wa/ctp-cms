<?php

namespace App\Rules;

use App\Support\Slugs\RootSlugs;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Admin validation for a slug that will live at domain/{slug} (D9).
class AvailableRootSlug implements ValidationRule
{
    public function __construct(private ?Model $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = Str::slug((string) $value);

        if (RootSlugs::isReserved($slug)) {
            $fail("“{$slug}” is reserved for a section of the site. Choose another slug.");

            return;
        }

        if (! RootSlugs::isAvailable($slug, $this->ignore)) {
            $fail("“{$slug}” is already used by another tutorial or page.");
        }
    }
}
