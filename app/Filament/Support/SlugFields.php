<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

// PRD SLUG-01, SLUG-04: title → slug while the slug is untouched, plus a validated slug input.
class SlugFields
{
    public static function source(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                // Follow the title only while the slug still matches the previous title.
                if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                    $set('slug', Str::slug((string) $state));
                }
            });
    }

    public static function slug(string $pathPrefix): TextInput
    {
        return TextInput::make('slug')
            ->label('URL slug')
            ->required()
            ->maxLength(255)
            ->prefix(rtrim(url($pathPrefix), '/').'/')
            ->unique(ignoreRecord: true)
            ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->validationMessages(['regex' => 'Use lowercase letters, numbers and single hyphens only.'])
            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                ? 'Changing a published slug redirects the old address automatically.'
                : null);
    }
}
