<?php

namespace App\Filament\Support;

use App\Filament\Forms\Components\MediaPicker;
use App\Seo\SeoChecklist;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html as HtmlBlock;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

// PRD SEO-01: per-item overrides stored in seo_meta (the model needs a `seo()` morphOne).
class SeoSection
{
    /**
     * @param  string  $titleField  form field with the item's title
     * @param  string  $descriptionField  form field used when the SEO description is empty
     * @param  string|Closure  $pathPrefix  public path before the slug, e.g. "/" or "/projects/"
     * @param  string|null  $bodyField  TipTap body for the content checks (null: skip them)
     * @param  Action|null  $generate  header button that fills the fields (AI)
     */
    public static function make(
        string $fallbacks = 'the title and excerpt',
        string $imageHint = 'Defaults to a generated card with the title.',
        string $titleField = 'title',
        string $descriptionField = 'excerpt',
        string|Closure $pathPrefix = '/',
        ?string $bodyField = 'body',
        ?Action $generate = null,
    ): Section {
        return Section::make('Search engines & social')->key('seo-section')
            ->description("Leave empty to use {$fallbacks}.")
            ->collapsed()
            ->headerActions($generate ? [$generate] : [])
            ->schema([
                // SEO-02: live previews and checks from the unsaved form.
                HtmlBlock::make(fn (Get $get) => view('filament.seo.preview', [
                    'title' => filled($get('seo.title')) ? $get('seo.title') : $get($titleField),
                    'description' => filled($get('seo.description')) ? $get('seo.description') : $get($descriptionField),
                    'url' => rtrim(url('/'), '/').(is_string($pathPrefix) ? $pathPrefix : $pathPrefix($get)).$get('slug'),
                    'site' => app(GeneralSettings::class)->site_name,
                    'format' => app(SeoSettings::class)->title_format,
                    'checks' => SeoChecklist::run(
                        filled($get('seo.title')) ? $get('seo.title') : $get($titleField),
                        filled($get('seo.description')) ? $get('seo.description') : $get($descriptionField),
                        $get('slug'),
                        $bodyField && is_array($get($bodyField)) ? $get($bodyField) : null,
                        hasBody: $bodyField !== null,
                    ),
                ])->render()),
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
