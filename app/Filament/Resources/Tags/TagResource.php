<?php

namespace App\Filament\Resources\Tags;

use App\Filament\Resources\Tags\Pages\ManageTags;
use App\Filament\Support\SlugFields;
use App\Models\Tag;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

// PRD TAX-02: tags with usage counts and merging.
class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 21;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                SlugFields::source('name', 'Name'),
                SlugFields::slug('/tag'),
                Textarea::make('description')->rows(2)->maxLength(500)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->select('tags.*')->selectSub(
                DB::table('article_tag')->selectRaw('count(*)')->whereColumn('tag_id', 'tags.id'),
                'articles_count',
            ))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->color('gray')->searchable(),
                TextColumn::make('articles_count')->label('Articles')->badge()->sortable()
                    ->color(fn (int $state) => $state === 0 ? 'gray' : 'primary'),
            ])
            ->filters([
                Filter::make('unused')->label('Unused only')
                    ->query(fn (Builder $query) => $query->whereNotExists(fn ($q) => $q->from('article_tag')->whereColumn('tag_id', 'tags.id'))),
            ])
            ->recordActions([
                self::mergeAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mergeSelected')
                        ->label('Merge into…')
                        ->icon(Heroicon::OutlinedArrowsPointingIn)
                        ->schema([self::targetSelect()])
                        ->action(function (Collection $records, array $data) {
                            $target = Tag::findOrFail($data['target_id']);
                            /** @var Collection<int, Tag> $records */
                            foreach ($records as $tag) {
                                $tag->mergeInto($target); // no-op for the target itself
                            }
                            Notification::make()->success()->title("Merged into “{$target->name}”")->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function mergeAction(): Action
    {
        return Action::make('merge')
            ->label('Merge')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('gray')
            ->modalDescription(fn (Tag $record) => "Articles tagged “{$record->name}” move to the tag you choose, /tag/{$record->slug} redirects there, and “{$record->name}” is deleted.")
            ->schema(fn (Tag $record) => [self::targetSelect($record)])
            ->action(function (Tag $record, array $data) {
                $target = Tag::findOrFail($data['target_id']);
                $record->mergeInto($target);
                Notification::make()->success()->title("Merged into “{$target->name}”")->send();
            });
    }

    private static function targetSelect(?Tag $except = null): Select
    {
        return Select::make('target_id')
            ->label('Merge into')
            ->required()
            ->searchable()
            ->options(fn () => Tag::query()->when($except, fn ($q) => $q->whereKeyNot($except->getKey()))->orderBy('name')->pluck('name', 'id'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTags::route('/'),
        ];
    }
}
