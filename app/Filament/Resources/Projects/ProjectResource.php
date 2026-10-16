<?php

namespace App\Filament\Resources\Projects;

use App\Enums\ProjectStatus;
use App\Filament\Forms\Components\MediaPicker;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Support\ContentEditor;
use App\Filament\Support\SeoSection;
use App\Filament\Support\SlugFields;
use App\Filament\Support\TechnologyPicker;
use App\Models\Project;
use App\Models\Technology;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

// PRD PUB-09: portfolio projects at /projects/{slug}. Public pages arrive in T2.10.
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 25;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make([
                    Section::make()->columns(1)->schema([
                        SlugFields::source('title', 'Name')->extraInputAttributes(['class' => 'text-lg font-semibold']),
                        SlugFields::slug('/projects'),
                        TextInput::make('tagline')->label('One-line pitch')->maxLength(160)
                            ->helperText('Shown on the project card and used as the meta description fallback.'),
                        ContentEditor::make(minHeight: '24rem')->label('Write-up')
                            ->helperText('Problem → solution: what it does, why it exists, how it was built and what you learned. Blocks work here too.'),
                    ]),
                    Section::make('Gallery')
                        ->description('Screenshots shown below the write-up, in this order.')
                        ->collapsible()
                        ->schema([
                            Repeater::make('gallery')->hiddenLabel()
                                ->schema([
                                    MediaPicker::make('media_id')->hiddenLabel()->required(),
                                    TextInput::make('caption')->maxLength(200)->placeholder('Optional caption'),
                                ])
                                ->grid(['default' => 1, 'md' => 2])
                                ->defaultItems(0)->reorderable()->addActionLabel('Add screenshot'),
                        ]),
                    SeoSection::make('the name, pitch and cover image', 'Defaults to the cover image, then a generated card.'),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Publish')->schema([
                        Toggle::make('is_published')->label('Published')->default(true),
                        Toggle::make('is_featured')->label('Featured on the home page'),
                        ToggleButtons::make('status')->required()->inline()->default(ProjectStatus::Active->value)
                            ->options(ProjectStatus::class),
                    ]),
                    Section::make('Cover image')->schema([
                        MediaPicker::make('cover_media_id')->hiddenLabel(),
                    ]),
                    Section::make('Stack & links')->schema([
                        TechnologyPicker::make('technologies')->label('Tech stack')->multiple()
                            ->relationship('technologies', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Technology $record) => TechnologyPicker::label($record))
                            // Keep the order the technologies were picked in.
                            ->saveRelationshipsUsing(fn (Project $record, ?array $state) => $record->technologies()->sync(
                                collect($state ?? [])->values()->mapWithKeys(fn ($id, $i) => [(int) $id => ['sort' => $i]])->all()
                            ))
                            ->helperText('Shown as logos on the project and used for “filter by tech”.'),
                        TextInput::make('live_url')->label('Live URL')->url()->prefixIcon(Heroicon::OutlinedGlobeAlt),
                        TextInput::make('repo_url')->label('Repository URL')->url()->prefixIcon(Heroicon::OutlinedCodeBracket),
                    ]),
                    Section::make('Timeline')->columns(2)->schema([
                        DatePicker::make('started_at')->label('Started'),
                        DatePicker::make('ended_at')->label('Ended')
                            ->afterOrEqual('started_at')
                            ->helperText(fn (Get $get) => $get('status') === ProjectStatus::Archived->value || $get('status') === ProjectStatus::Archived ? null : 'Empty = ongoing.'),
                    ]),
                    Section::make('Related tutorials')->schema([
                        Select::make('articles')->hiddenLabel()->multiple()->searchable()->preload()
                            ->relationship('articles', 'title')
                            // Keep the order the tutorials were picked in.
                            ->saveRelationshipsUsing(fn (Project $record, ?array $state) => $record->articles()->sync(
                                collect($state ?? [])->values()->mapWithKeys(fn ($id, $i) => [(int) $id => ['sort' => $i]])->all()
                            ))
                            ->helperText('Tutorials written while building this project, in reading order.'),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->modifyQueryUsing(fn ($query) => $query->with('cover'))
            ->columns([
                ImageColumn::make('cover')->label('')->state(fn (Project $record) => $record->cover?->thumbUrl())->imageWidth(64)->imageHeight(40),
                TextColumn::make('title')->label('Name')->searchable()->sortable()->wrap()
                    ->description(fn (Project $record) => $record->tagline ? str($record->tagline)->limit(60) : null),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('technologies.name')->label('Stack')->badge()->color('gray')->limitList(2)->expandableLimitedList()->visibleFrom('xl'),
                IconColumn::make('is_featured')->label('Featured')->boolean()->trueIcon(Heroicon::Star)->falseIcon(Heroicon::OutlinedStar)->trueColor('warning'),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ProjectStatus::class),
                TernaryFilter::make('is_published')->label('Published'),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->recordActions([
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
