<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Settings\DisplaySettings;
use App\Settings\GeneralSettings;
use App\Settings\IntegrationSettings;
use App\Settings\NoticeSettings;
use App\Settings\RibbonSettings;
use App\Settings\ScriptSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;
use App\Support\Contrast;
use App\Support\Html;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html as HtmlBlock;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Spatie\LaravelSettings\Settings;
use UnitEnum;

/**
 * PRD SET-01..07: one page, one tab per settings group.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    /** @var array<string, class-string<Settings>> form key => settings class */
    private const GROUPS = [
        'general' => GeneralSettings::class,
        'display' => DisplaySettings::class,
        'seo' => SeoSettings::class,
        'notice' => NoticeSettings::class,
        'ribbon' => RibbonSettings::class,
        'social' => SocialSettings::class,
        'scripts' => ScriptSettings::class,
        'integrations' => IntegrationSettings::class,
    ];

    private const RIBBON_PRESETS = [
        '#0f766e' => 'Brand teal',
        '#b45309' => 'Amber',
        '#b91c1c' => 'Red',
        '#1d4ed8' => 'Blue',
        '#334155' => 'Slate',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $data = [];
        foreach (self::GROUPS as $key => $class) {
            $data[$key] = app($class)->toArray();
        }

        $data['integrations']['ai_crawlers'] = array_keys(array_filter($data['integrations']['ai_crawlers']));

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        $this->generalTab(),
                        $this->layoutTab(),
                        $this->seoTab(),
                        $this->noticeTab(),
                        $this->ribbonTab(),
                        $this->socialTab(),
                        $this->scriptsTab(),
                        $this->integrationsTab(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky()->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $data['notice']['message'] = Html::inline($data['notice']['message'] ?? null) ?: null;
        $data['integrations']['ai_crawlers'] = collect(IntegrationSettings::AI_CRAWLERS)
            ->mapWithKeys(fn (string $label, string $bot): array => [$bot => in_array($bot, $data['integrations']['ai_crawlers'] ?? [], true)])
            ->all();

        foreach (self::GROUPS as $key => $class) {
            app($class)->fill($data[$key])->save();
        }

        Notification::make()->success()->title('Settings saved')->send();
    }

    private function generalTab(): Tab
    {
        return Tab::make('General')->icon(Heroicon::OutlinedGlobeAlt)->schema([
            Group::make()->statePath('general')->schema([
                Section::make('Identity')->columns(2)->schema([
                    TextInput::make('site_name')->label('Site name')->required()->maxLength(100),
                    TextInput::make('site_abbr')->label('Abbreviation')->required()->maxLength(10),
                    TextInput::make('slogan')->maxLength(150),
                    TextInput::make('organization_name')->label('Organisation name')->maxLength(150),
                    Textarea::make('description')->label('Short description')->rows(3)->maxLength(300)->columnSpanFull()
                        ->helperText('Used in the footer and as the fallback site description.'),
                ]),
                Section::make('Contact & domain')->columns(2)->schema([
                    TextInput::make('domain_name')->label('Domain name')->required()->prefix('https://')
                        ->helperText(fn (): ?string => parse_url(config('app.url'), PHP_URL_HOST) !== null
                            ? 'APP_URL in .env is '.config('app.url').'. Routing always uses APP_URL.' : null),
                    TextInput::make('contact_email')->label('Contact email')->email(),
                    TextInput::make('notification_email')->label('Notification email')->email()
                        ->helperText('Receives contact form and comment notifications.'),
                    TextInput::make('copyright')->label('Copyright holder')->helperText('Shown as © {year} {holder}.'),
                    Select::make('default_author_id')->label('Default author')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                        ->helperText('Pre-selected on new articles; legacy articles are assigned to this author.'),
                ]),
                Section::make('Branding')->columns(3)->schema([
                    FileUpload::make('logo_light')->label('Logo (light theme)')->image()->disk('public')->directory('settings'),
                    FileUpload::make('logo_dark')->label('Logo (dark theme)')->image()->disk('public')->directory('settings'),
                    FileUpload::make('default_og_image')->label('Default social image')->image()->disk('public')->directory('settings')
                        ->helperText('1200 × 630. Used when a page has no image of its own.'),
                ]),
            ]),
        ]);
    }

    private function layoutTab(): Tab
    {
        return Tab::make('Layout')->icon(Heroicon::OutlinedViewColumns)->schema([
            Group::make()->statePath('display')->schema([
                Section::make('Category sidebar')
                    ->description('The left column on tutorial pages and the home page.')
                    ->schema([
                        TextInput::make('sidebar_items_per_category')->label('Tutorials per category')
                            ->required()->integer()->minValue(1)->maxValue(DisplaySettings::SIDEBAR_MAX)
                            ->suffix('tutorials')
                            ->helperText('The newest tutorials listed under each category before the “View all” link. The tutorial being read is always shown.'),
                    ]),
            ]),
        ]);
    }

    private function seoTab(): Tab
    {
        return Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
            Group::make()->statePath('seo')->schema([
                Section::make('Defaults')->description('Each page can override these.')->columns(2)->schema([
                    TextInput::make('title_format')->label('Title format')->required()
                        ->helperText('Placeholders: {page} and {site}.'),
                    TextInput::make('meta_title')->label('Meta title (home)')->maxLength(70),
                    Textarea::make('meta_description')->label('Meta description')->rows(3)->maxLength(320)->columnSpanFull()
                        ->hint(fn (?string $state): string => strlen((string) $state).' / 160 recommended')->live(onBlur: true),
                    TextInput::make('meta_keywords')->label('Meta keywords')->columnSpanFull()
                        ->helperText('Comma-separated. Google ignores this tag; some other tools still read it.'),
                    TextInput::make('meta_author')->label('Meta author'),
                    TextInput::make('meta_copyright')->label('Meta copyright'),
                    Select::make('default_robots')->label('Default robots')->required()->options([
                        'index, follow' => 'index, follow',
                        'noindex, follow' => 'noindex, follow',
                        'noindex, nofollow' => 'noindex, nofollow (whole site hidden)',
                    ]),
                ]),
                Section::make('Search engine verification')->columns(2)->schema([
                    TextInput::make('google_verification')->label('Google Search Console code')
                        ->helperText('Only the content value of the meta tag.'),
                    TextInput::make('bing_verification')->label('Bing Webmaster code'),
                ]),
                Section::make('robots.txt')
                    ->description(new HtmlString('Generated at <a href="'.e(url('/robots.txt')).'" target="_blank" class="text-primary-600 underline">/robots.txt</a>: admin, previews, search and the API are blocked; AI crawlers follow Integrations → AI crawlers; the sitemap is linked.'))
                    ->schema([
                        Textarea::make('robots_extra')->label('Extra rules')->rows(4)
                            ->placeholder("User-agent: AhrefsBot\nDisallow: /")
                            ->helperText('Appended as written. Leave empty unless you need to block or allow a specific bot or path.'),
                    ]),
            ]),
        ]);
    }

    private function noticeTab(): Tab
    {
        return Tab::make('Topbar notification')->icon(Heroicon::OutlinedMegaphone)->schema([
            Group::make()->statePath('notice')->schema([
                Section::make()->columns(2)->schema([
                    Toggle::make('enabled')->label('Show the notification bar')->columnSpanFull(),
                    RichEditor::make('message')->columnSpanFull()
                        ->toolbarButtons([['bold', 'italic', 'link']])
                        ->helperText('One short line. Bold, italic and links only.'),
                    ToggleButtons::make('style')->required()->inline()->options(NoticeSettings::STYLES)
                        ->colors(['info' => 'info', 'success' => 'success', 'warning' => 'warning', 'maintenance' => 'danger']),
                    Toggle::make('dismissible')->label('Visitors can dismiss it'),
                    TextInput::make('link_label')->label('Button label')->maxLength(30),
                    TextInput::make('link_url')->label('Button link')->url(),
                    DateTimePicker::make('starts_at')->label('Show from')->seconds(false)
                        ->timezone(config('ctp.display_timezone'))
                        ->helperText('Optional. Times are '.config('ctp.display_timezone').'.'),
                    DateTimePicker::make('ends_at')->label('Hide after')->seconds(false)->after('starts_at')
                        ->timezone(config('ctp.display_timezone')),
                ]),
            ]),
        ]);
    }

    private function ribbonTab(): Tab
    {
        return Tab::make('Ribbon')->icon(Heroicon::OutlinedBookmark)->schema([
            Group::make()->statePath('ribbon')->schema([
                Section::make()->columns(2)->schema([
                    Toggle::make('enabled')->label('Show the corner ribbon')->live()->columnSpanFull(),
                    TextInput::make('title')->required()->maxLength(20)->live(onBlur: true),
                    TextInput::make('url')->label('Link')->url(),
                    Toggle::make('new_tab')->label('Open link in a new tab'),
                    ToggleButtons::make('preset')->label('Colour presets')->inline()->dehydrated(false)
                        ->options(self::RIBBON_PRESETS)->live()
                        ->afterStateUpdated(fn (?string $state, Set $set) => $state ? $set('bg_color', $state) : null),
                    ColorPicker::make('bg_color')->label('Background colour')->hex()->required()->live()
                        ->regex('/^#[0-9a-fA-F]{6}$/'),
                    Select::make('text_color')->label('Text colour')->required()->live()->options([
                        'auto' => 'Automatic (best contrast)',
                        Contrast::LIGHT => 'White',
                        Contrast::DARK => 'Dark',
                    ]),
                    HtmlBlock::make(fn (Get $get): HtmlString => self::ribbonPreview($get))->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    private function socialTab(): Tab
    {
        return Tab::make('Social')->icon(Heroicon::OutlinedShare)->schema([
            Group::make()->statePath('social')->schema([
                Section::make()->columns(2)->schema(
                    collect(SocialSettings::NETWORKS)
                        ->map(fn (string $label, string $key) => TextInput::make($key)->label($label)->url()->prefixIcon(Heroicon::OutlinedLink))
                        ->values()
                        ->all(),
                ),
            ]),
        ]);
    }

    private function scriptsTab(): Tab
    {
        return Tab::make('Scripts')->icon(Heroicon::OutlinedCodeBracket)->schema([
            Group::make()->statePath('scripts')->schema([
                Section::make()->description('Output exactly as entered, on every public page. Use for analytics (e.g. the CTP SaaS snippet).')->schema([
                    CodeEditor::make('head_scripts')->label('Before </head>')->language(Language::Html),
                    CodeEditor::make('body_scripts')->label('Before </body>')->language(Language::Html),
                    Toggle::make('cookie_notice')->label('Show a cookie notice')
                        ->helperText('Only needed for tracking cookies such as Google Analytics.'),
                ]),
            ]),
        ]);
    }

    private function integrationsTab(): Tab
    {
        return Tab::make('Integrations')->icon(Heroicon::OutlinedPuzzlePiece)->schema([
            Group::make()->statePath('integrations')->schema([
                Section::make('Comments')->columns(2)->schema([
                    Toggle::make('comments_enabled')->label('Allow comments site-wide'),
                    TextInput::make('comments_auto_close_days')->label('Close comments after (days)')->numeric()->minValue(1)
                        ->helperText('Empty = never.'),
                    Toggle::make('comments_notify_admin')->label('Email me about new comments')
                        ->helperText('Sent to the notification email (General tab). First-time commenters wait for approval.'),
                ]),
                Section::make('Cloudflare Turnstile')->description('Spam protection for comments and the contact form.')->columns(2)->schema([
                    TextInput::make('turnstile_site_key')->label('Site key'),
                    TextInput::make('turnstile_secret_key')->label('Secret key')->password()->revealable(),
                ]),
                Section::make('Search & AI')->columns(2)->schema([
                    TextInput::make('indexnow_key')->label('IndexNow key')
                        ->hintAction(Action::make('generate')->label('Generate')
                            ->action(fn (Set $set) => $set('indexnow_key', bin2hex(random_bytes(16))))),
                    CheckboxList::make('ai_crawlers')->label('AI crawlers allowed in robots.txt')
                        ->options(IntegrationSettings::AI_CRAWLERS)->columns(2)->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    private static function ribbonPreview(Get $get): HtmlString
    {
        $bg = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $get('bg_color')) ? $get('bg_color') : '#0f766e';
        $fg = $get('text_color') === 'auto' || ! $get('text_color') ? Contrast::readableOn($bg) : $get('text_color');
        $title = e($get('title') ?: 'Ribbon title');

        return new HtmlString(<<<HTML
            <div style="position:relative;height:120px;overflow:hidden;border-radius:12px;border:1px dashed rgb(148 163 184 / .6);background:repeating-linear-gradient(45deg,transparent 0 10px,rgb(148 163 184 / .08) 10px 20px)">
                <span style="position:absolute;left:16px;top:14px;font-size:12px;color:rgb(100 116 139)">Preview (top-right corner of the site)</span>
                <div style="position:absolute;top:30px;right:-44px;width:180px;transform:rotate(45deg);background:{$bg};color:{$fg};text-align:center;padding:6px 0;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;box-shadow:0 4px 10px -4px rgb(0 0 0 / .35)">{$title}</div>
            </div>
            HTML);
    }
}
