<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Ai\AiException;
use App\Ai\ArticleText;
use App\Ai\DeepSeek;
use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\Difficulty;
use App\Filament\Forms\Components\MediaPicker;
use App\Filament\Resources\Technologies\TechnologyResource;
use App\Filament\Support\ContentEditor;
use App\Filament\Support\SeoSection;
use App\Filament\Support\TechnologyPicker;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Seo\LinkSuggestions;
use App\Settings\GeneralSettings;
use App\Support\Slugs\RootSlugs;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html as HtmlBlock;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// PRD ART-01..12, EDIT-01..06: the article editor. Main column + WordPress-style sidebar.
class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make([
                    self::contentSection(),
                    self::tldrSection(),
                    self::tutorialSection(),
                    self::faqSection(),
                    SeoSection::make(pathPrefix: fn (Get $get) => in_array($get('type'), [ArticleType::Blog, ArticleType::Blog->value], true) ? '/blog/' : '/'),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    self::publishSection(),
                    self::organisationSection(),
                    Section::make('Featured image')->schema([
                        MediaPicker::make('featured_media_id')->hiddenLabel(),
                    ]),
                    self::linkSuggestionsSection(),
                    self::optionsSection(),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    private static function contentSection(): Section
    {
        return Section::make()->schema([
            TextInput::make('title')->required()->maxLength(255)
                ->extraInputAttributes(['class' => 'text-lg font-semibold'])
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state, string $operation) {
                    if ($operation === 'create' && (blank($get('slug')) || $get('slug') === Str::slug((string) $old))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')->label('URL slug')->required()->maxLength(255)
                ->prefix(fn (Get $get) => rtrim(url($get('type') === ArticleType::Blog->value || $get('type') === ArticleType::Blog ? '/blog' : '/'), '/').'/')
                ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->rule(fn (Get $get, ?Article $record) => Rule::unique('articles', 'slug')
                    ->where('type', $get('type') instanceof ArticleType ? $get('type')->value : $get('type'))
                    ->ignore($record?->getKey()))
                // Tutorials share domain/{slug} with pages and must avoid reserved words (D9).
                ->rule(fn (Get $get, ?Article $record) => function (string $attribute, mixed $value, Closure $fail) use ($get, $record) {
                    $type = $get('type') instanceof ArticleType ? $get('type') : ArticleType::tryFrom((string) $get('type'));
                    if ($type !== ArticleType::Tutorial) {
                        return;
                    }
                    if (RootSlugs::isReserved((string) $value)) {
                        $fail("“{$value}” is reserved for a section of the site.");
                    } elseif (! RootSlugs::isAvailable((string) $value, $record)) {
                        $fail("“{$value}” is already used by a page or another tutorial.");
                    }
                })
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Changing the slug of a published article redirects the old URL automatically.' : null),
            Textarea::make('excerpt')->rows(2)->maxLength(500)->live(onBlur: true)
                ->helperText('One or two sentences for cards, search results and the meta description fallback.'),
            ContentEditor::make(),
        ]);
    }

    private static function tldrSection(): Section
    {
        return Section::make('Key takeaways (TL;DR)')->key('tldr-section')
            ->description('Shown at the top of the article. Short, factual points: AI search engines quote these. Write them yourself, or let DeepSeek draft them from the article and then edit.')
            ->collapsible()
            ->headerActions([self::generateTldrAction()])
            ->schema([
                Repeater::make('tldr')->hiddenLabel()
                    ->simple(TextInput::make('point')->required()->maxLength(200))
                    ->reorderable()->defaultItems(0)->addActionLabel('Add takeaway')->maxItems(6),
            ]);
    }

    // The article text (title and prose) goes to DeepSeek; the answer only fills the field, the admin edits and saves.
    private static function generateTldrAction(): Action
    {
        return Action::make('generateTldr')
            ->label('Generate with AI')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->requiresConfirmation(fn (Get $get): bool => filled($get('tldr')))
            ->modalHeading('Replace the current takeaways?')
            ->modalDescription('The takeaways below will be replaced by new ones from DeepSeek. You can still edit them before saving.')
            ->action(function (Get $get, Set $set): void {
                try {
                    $points = app(DeepSeek::class)->keyTakeaways((string) $get('title'), ArticleText::from($get('body')));
                } catch (AiException $e) {
                    Notification::make()->danger()->title('Could not generate takeaways')->body($e->getMessage())->send();

                    return;
                }

                $set('tldr', $points);
                Notification::make()->success()->title('Takeaways generated')->body('Read them, edit what is off, then save the article.')->send();
            });
    }

    private static function tutorialSection(): Section
    {
        return Section::make('Tutorial details')
            ->collapsible()
            ->visible(fn (Get $get) => self::isTutorial($get))
            ->columns(2)
            ->schema([
                ToggleButtons::make('difficulty')->inline()->options(Difficulty::class),
                TextInput::make('reading_minutes_override')->label('Reading time (minutes)')->numeric()->minValue(1)->maxValue(240)
                    ->placeholder(fn (?Article $record) => $record ? "Auto: {$record->reading_minutes}" : 'Calculated on save'),
                Repeater::make('prerequisites')
                    ->simple(TextInput::make('item')->required()->maxLength(200))
                    ->defaultItems(0)->addActionLabel('Add prerequisite')->reorderable()->columnSpanFull(),
                TextInput::make('repo_url')->label('Source code URL')->url()->placeholder('https://github.com/…'),
                TextInput::make('demo_url')->label('Live demo URL')->url(),
            ]);
    }

    private static function faqSection(): Section
    {
        return Section::make('FAQ')
            ->description('Optional questions and answers, published as FAQ structured data.')
            ->collapsed()
            ->schema([
                Repeater::make('faq')->hiddenLabel()
                    ->schema([
                        TextInput::make('question')->required()->maxLength(255),
                        Textarea::make('answer')->required()->rows(2),
                    ])
                    ->itemLabel(fn (array $state) => $state['question'] ?? null)
                    ->collapsible()->defaultItems(0)->addActionLabel('Add question')->reorderable(),
            ]);
    }

    private static function publishSection(): Section
    {
        return Section::make('Publish')->schema([
            ToggleButtons::make('status')->hiddenLabel()->inline()->required()->default(ArticleStatus::Draft->value)
                ->options([ArticleStatus::Draft->value => 'Draft', ArticleStatus::Published->value => 'Publish'])
                ->colors([ArticleStatus::Draft->value => 'gray', ArticleStatus::Published->value => 'success'])
                ->icons([ArticleStatus::Draft->value => 'heroicon-o-pencil-square', ArticleStatus::Published->value => 'heroicon-o-globe-alt'])
                // "Scheduled" is stored but shown as Publish (it is a publish with a future date).
                ->formatStateUsing(fn ($state) => ($state instanceof ArticleStatus ? $state : ArticleStatus::tryFrom((string) $state)) === ArticleStatus::Draft || $state === null ? ArticleStatus::Draft->value : ArticleStatus::Published->value)
                ->live(),
            DateTimePicker::make('published_at')->label('Publish date')->seconds(false)
                ->timezone(config('ctp.display_timezone'))
                ->helperText('Empty = now. A future date schedules the article.')
                ->visible(fn (Get $get) => $get('status') === ArticleStatus::Published->value),
            Toggle::make('meaningful_update')->label('Meaningful update')->dehydrated(false)
                ->helperText('Shows “Last updated” to readers. Leave off for typo fixes.')
                ->visible(fn (string $operation, ?Article $record) => $operation === 'edit' && $record?->published_at !== null),
            Text::make(fn (?Article $record) => self::statusSummary($record))->visible(fn (?Article $record) => $record !== null),
            Grid::make(2)->schema([
                ToggleButtons::make('type')->required()->inline()->grouped()->default(ArticleType::Tutorial->value)
                    ->options(ArticleType::class)->live()->columnSpanFull(),
                Select::make('author_id')->label('Author')->required()->columnSpanFull()
                    ->options(fn () => User::query()->orderBy('name')->get()->mapWithKeys(fn (User $u) => [$u->id => $u->authorName()]))
                    ->default(fn () => app(GeneralSettings::class)->default_author_id ?? auth()->id()),
            ]),
        ]);
    }

    // SEO-12: related published articles to link to, from the unsaved title, tags and category.
    private static function linkSuggestionsSection(): Section
    {
        return Section::make('Link suggestions')
            ->description('Copy a link, select words in the content and press the link button.')
            ->collapsible()->collapsed()
            ->schema([
                HtmlBlock::make(fn (Get $get, ?Article $record) => view('filament.seo.link-suggestions', [
                    'suggestions' => LinkSuggestions::for(
                        $record?->getKey(),
                        $get('title'),
                        array_values(array_filter((array) $get('tags'))),
                        filled($get('category_id')) ? (int) $get('category_id') : null,
                        is_array($get('body')) ? $get('body') : null,
                    ),
                ])->render()),
            ]);
    }

    private static function organisationSection(): Section
    {
        return Section::make('Organisation')->schema([
            Select::make('category_id')->label('Category')->live()
                ->options(fn () => Category::treeOptions())
                ->searchable()
                ->required(fn (Get $get) => self::isTutorial($get))
                ->visible(fn (Get $get) => self::isTutorial($get)),
            Select::make('tags')->relationship('tags', 'name')->multiple()->searchable()->preload()->live()
                ->createOptionForm([TextInput::make('name')->required()->maxLength(60)]),
            // The first row is the main technology: its logo shows in lists. Versions show as "Tested with".
            Repeater::make('techStack')->label('Tech stack')
                ->relationship('techStack')
                ->orderColumn('sort')
                ->table([
                    TableColumn::make('Technology')->markAsRequired(),
                    TableColumn::make('Version')->width('5.5rem'),
                ])
                ->schema([
                    // No inline "create" here: the sidebar is narrow. New ones are added under Content → Technologies.
                    TechnologyPicker::make('technology_id', creatable: false)->label('Technology')->required()->distinct(),
                    TextInput::make('version')->label('Version')->placeholder('Optional, e.g. 8.4')->maxLength(30),
                ])
                ->defaultItems(0)->reorderable()->addActionLabel('Add technology')
                ->hint(new HtmlString('<a href="'.e(TechnologyResource::getUrl()).'" target="_blank" class="text-primary-600 hover:underline">Manage</a>'))
                ->helperText('Drag the main one to the top: its logo shows in lists. Versions show as “Tested with”.'),
            Grid::make(3)->visible(fn (Get $get) => self::isTutorial($get))->schema([
                Select::make('series_id')->label('Series')->relationship('series', 'title')->searchable()->preload()->live()->columnSpan(2),
                TextInput::make('series_position')->label('Part')->numeric()->minValue(1)
                    ->required(fn (Get $get) => filled($get('series_id'))),
            ]),
        ]);
    }

    private static function optionsSection(): Section
    {
        return Section::make('Options')->collapsed()->schema([
            Toggle::make('is_featured')->label('Featured on the home page'),
            Toggle::make('is_pinned')->label('Pinned to the top of lists'),
            Toggle::make('comments_enabled')->label('Allow comments')->default(true),
            Toggle::make('needs_review')->label('Needs review')
                ->helperText('Shows readers a “may be outdated” notice and lists the article under Needs review.'),
        ]);
    }

    private static function statusSummary(?Article $record): HtmlString
    {
        if ($record === null) {
            return new HtmlString('');
        }

        $tz = config('ctp.display_timezone');
        $lines = [
            'Status: <strong>'.e($record->status->getLabel()).'</strong>'.($record->published_at ? ' · '.e($record->published_at->timezone($tz)->format('j M Y, g:i a')) : ''),
            e(number_format($record->word_count)).' words · '.e($record->readingMinutes()).' min read · '.e(number_format($record->views)).' views',
        ];
        if ($record->content_updated_at) {
            $lines[] = 'Last meaningful update: '.e($record->content_updated_at->timezone($tz)->format('j M Y'));
        }
        if ($record->isOutdated()) {
            $lines[] = '<span style="color: rgb(217 119 6)">Readers see a “may be outdated” notice.</span>';
        }

        return new HtmlString('<div style="font-size: .8125rem; line-height: 1.6; color: rgb(100 116 139)">'.implode('<br>', $lines).'</div>');
    }

    private static function isTutorial(Get $get): bool
    {
        $type = $get('type');

        return ($type instanceof ArticleType ? $type : ArticleType::tryFrom((string) $type)) !== ArticleType::Blog;
    }
}
