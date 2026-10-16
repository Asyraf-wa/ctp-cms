<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\ContentEditor;
use App\Filament\Support\SeoSection;
use App\Models\Page;
use App\Rules\AvailableRootSlug;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

// PRD PUB-10: About, Privacy, Terms, Uses… at domain/{slug}, sharing the namespace with tutorials (D9).
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make([
                    Section::make()->schema([
                        TextInput::make('title')->required()->maxLength(255)
                            ->extraInputAttributes(['class' => 'text-lg font-semibold'])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')->label('URL slug')->required()->maxLength(255)
                            ->prefix(rtrim(url('/'), '/').'/')
                            ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->validationMessages(['regex' => 'Use lowercase letters, numbers and single hyphens only.'])
                            ->rule(fn (?Page $record) => new AvailableRootSlug($record))
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Changing the slug of a published page redirects the old URL automatically.' : null),
                        Textarea::make('excerpt')->label('Summary')->rows(2)->maxLength(500)
                            ->helperText('Used as the meta description when the SEO description is empty.'),
                        ContentEditor::make(minHeight: '24rem'),
                    ]),
                    SeoSection::make('the title and summary'),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Publish')->schema([
                        Toggle::make('is_published')->label('Published')->default(true)
                            ->helperText('Unpublished pages return 404 to visitors.'),
                        Select::make('template')->required()->default('default')->options(Page::TEMPLATES)->native(false),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()
                    ->description(fn (Page $record) => '/'.$record->slug),
                TextColumn::make('template')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => Str::before(Page::TEMPLATES[$state] ?? $state, ':')),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('updated_at')->label('Updated')->since()->sortable()
                    ->timezone(config('ctp.display_timezone')),
            ])
            ->recordActions([
                Action::make('view')->label('View')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (Page $record) => url($record->publicPath() ?? '/'), shouldOpenInNewTab: true)
                    ->visible(fn (Page $record) => $record->isPubliclyVisible()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
