<?php

namespace App\Filament\Support;

use App\Filament\Forms\Components\MediaPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

// PRD SEO-01: per-item overrides stored in seo_meta (the model needs a `seo()` morphOne).
class SeoSection
{
    public static function make(string $fallbacks = 'the title, excerpt and featured image', string $imageHint = 'Defaults to the featured image, then a generated card.'): Section
    {
        return Section::make('Search engines & social')
            ->description("Leave empty to use {$fallbacks}.")
            ->collapsed()
            ->schema([
                Group::make()->relationship('seo')->columns(2)->schema([
                    TextInput::make('title')->label('SEO title')->maxLength(70)
                        ->hint(fn (?string $state) => strlen((string) $state).' / 60')->live(onBlur: true)
                        ->columnSpanFull(),
                    Textarea::make('description')->label('Meta description')->rows(2)->maxLength(320)
                        ->hint(fn (?string $state) => strlen((string) $state).' / 160')->live(onBlur: true)
                        ->columnSpanFull(),
                    TextInput::make('keywords')->label('Meta keywords')->columnSpanFull(),
                    TextInput::make('canonical')->label('Canonical URL')->url()
                        ->helperText('Only when this content was first published elsewhere.'),
                    Select::make('robots')->placeholder('Site default')->options([
                        'index, follow' => 'index, follow',
                        'noindex, follow' => 'noindex, follow',
                        'noindex, nofollow' => 'noindex, nofollow',
                    ]),
                    MediaPicker::make('og_media_id')->label('Social sharing image')->columnSpanFull()
                        ->helperText("1200 × 630 works best. {$imageHint}"),
                ]),
            ]);
    }
}
