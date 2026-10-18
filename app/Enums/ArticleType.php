<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ArticleType: string implements HasColor, HasLabel
{
    case Tutorial = 'tutorial';
    case Blog = 'blog';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tutorial => 'Tutorial',
            self::Blog => 'Blog post',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Tutorial => 'primary',
            self::Blog => 'info',
        };
    }
}
