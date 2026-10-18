<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

// PRD PUB-09: the status badge on project cards.
enum ProjectStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Maintained = 'maintained';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Maintained => 'info',
            self::Archived => 'gray',
        };
    }
}
