<?php

namespace App\Filament\Resources\Media\Tables;

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Media\MediaUploader;
use App\Models\Media;
use App\Models\MediaFolder;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\Component;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $grid = ! ($livewire instanceof ListMedia) || $livewire->mediaView === 'grid';

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('folder')->withCount('usages'))
            ->defaultSort('created_at', 'desc')
            ->paginated([30, 60, 120])
            ->defaultPaginationPageOption(30)
            ->columns($grid ? self::gridColumns() : self::listColumns())
            ->contentGrid($grid ? ['default' => 2, 'sm' => 3, 'md' => 4, 'xl' => 5, '2xl' => 6] : null)
            ->searchable()
            ->searchPlaceholder('Search name, file or alt text')
            ->filters([
                SelectFilter::make('folder')
                    ->options(fn () => ['none' => 'Unfiled'] + MediaFolder::options())
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        null, '' => $query,
                        'none' => $query->whereNull('folder_id'),
                        default => $query->where('folder_id', $data['value']),
                    }),
                SelectFilter::make('type')
                    ->options(['image' => 'Images', 'svg' => 'SVG', 'document' => 'Documents (PDF, ZIP)'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'image' => $query->where('mime', 'like', 'image/%')->where('mime', '!=', 'image/svg+xml'),
                        'svg' => $query->where('mime', 'image/svg+xml'),
                        'document' => $query->where('mime', 'not like', 'image/%'),
                        default => $query,
                    }),
                TernaryFilter::make('used')
                    ->label('Usage')
                    ->placeholder('All files')
                    ->trueLabel('In use')
                    ->falseLabel('Unused')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('usages'),
                        false: fn (Builder $query) => $query->whereDoesntHave('usages'),
                    ),
            ])
            ->recordAction('edit')
            ->recordActions([
                // In the grid the whole card opens the panel; the button is just a small hint.
                $grid ? self::editAction()->iconButton() : self::editAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('move')
                        ->label('Move to folder')
                        ->icon(Heroicon::OutlinedFolderArrowDown)
                        ->schema([Select::make('folder_id')->label('Folder')->placeholder('Unfiled')->options(fn () => MediaFolder::options())])
                        ->action(fn (Collection $records, array $data) => Media::whereKey($records->modelKeys())->update(['folder_id' => $data['folder_id'] ?? null]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('delete')
                        ->label('Delete unused')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Files that are still used somewhere are skipped.')
                        ->action(function (Collection $records) {
                            /** @var Collection<int, Media> $records */
                            [$used, $unused] = $records->partition(fn (Media $media) => $media->usages_count > 0);
                            $unused->each(fn (Media $media) => $media->delete());
                            Notification::make()->success()
                                ->title($unused->count().' deleted'.($used->count() ? ', '.$used->count().' skipped because they are in use' : ''))
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /** @return array<int, Column|Component> */
    private static function gridColumns(): array
    {
        return [
            Stack::make([
                ViewColumn::make('preview')->view('filament.media.thumb')->grow()->extraAttributes(['class' => 'w-full']),
                Stack::make([
                    TextColumn::make('name')
                        ->state(fn (Media $record) => $record->displayName())
                        ->weight('medium')
                        ->size('sm')
                        ->lineClamp(1)
                        ->searchable(['name', 'filename', 'alt']),
                    TextColumn::make('details')
                        ->state(fn (Media $record) => collect([$record->dimensions(), $record->humanSize()])->filter()->implode(' · '))
                        ->color('gray')
                        ->size('xs'),
                ])->space(1),
            ])->space(3)->extraAttributes(['class' => 'w-full']),
        ];
    }

    /** @return array<int, Column> */
    private static function listColumns(): array
    {
        return [
            ViewColumn::make('preview')->view('filament.media.thumb-small')->label('')->width('1%'),
            TextColumn::make('name')
                ->state(fn (Media $record) => $record->displayName())
                ->description(fn (Media $record) => $record->filename)
                ->searchable(['name', 'filename', 'alt'])
                ->sortable(),
            TextColumn::make('extension')->label('Type')->badge()->formatStateUsing(fn (string $state) => strtoupper($state)),
            TextColumn::make('dimensions')->state(fn (Media $record) => $record->dimensions())->color('gray'),
            TextColumn::make('size')->formatStateUsing(fn (Media $record) => $record->humanSize())->sortable(),
            TextColumn::make('folder.name')->label('Folder')->placeholder('Unfiled')->toggleable(),
            TextColumn::make('usages_count')->label('Used')->badge()->color(fn (int $state) => $state ? 'primary' : 'gray'),
            TextColumn::make('created_at')->label('Uploaded')->since()->sortable(),
        ];
    }

    private static function editAction(): EditAction
    {
        return EditAction::make()
            ->slideOver()
            ->modalWidth('5xl')
            ->modalHeading(fn (Media $record) => $record->displayName())
            ->schema([
                Grid::make(['default' => 1, 'lg' => 5])->schema([
                    View::make('filament.media.details')->columnSpan(['lg' => 3]),
                    Group::make([
                        TextInput::make('name')->label('Title')->maxLength(255),
                        Textarea::make('alt')->label('Alt text')->rows(3)->maxLength(300)
                            ->helperText('Describe the image for screen readers and search engines. Required before inserting it into an article.'),
                        Textarea::make('caption')->rows(2)->maxLength(500),
                        TextInput::make('credit')->maxLength(255)->placeholder('Photo by …'),
                        Select::make('folder_id')->label('Folder')->placeholder('Unfiled')->options(fn () => MediaFolder::options()),
                    ])->columnSpan(['lg' => 2]),
                ]),
            ])
            ->extraModalFooterActions(fn (Media $record) => [
                Action::make('replace')
                    ->label('Replace file')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('gray')
                    ->modalDescription('The new file takes this item’s place everywhere it is used. The address stays the same when the file type is the same.')
                    ->schema([ListMedia::fileInput('file')->required()])
                    ->action(function (array $data, MediaUploader $uploader) use ($record) {
                        try {
                            $uploader->replace($record, $data['file']);
                            Notification::make()->success()->title('File replaced')->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title($e->validator->errors()->first())->send();
                        }
                    })
                    ->cancelParentActions(),
                DeleteAction::make()
                    ->record($record)
                    ->disabled($record->isInUse())
                    ->tooltip($record->isInUse() ? 'In use: remove it from the content listed under “Used in” first.' : null)
                    ->cancelParentActions(),
            ]);
    }
}
