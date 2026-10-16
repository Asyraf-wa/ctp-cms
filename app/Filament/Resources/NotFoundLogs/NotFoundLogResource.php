<?php

namespace App\Filament\Resources\NotFoundLogs;

use App\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\NotFoundLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

// SEO-11: the 404 log, most requested first, with "Create redirect" per row.
class NotFoundLogResource extends Resource
{
    protected static ?string $model = NotFoundLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = '404 log';

    protected static ?string $modelLabel = 'missing page';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = NotFoundLog::where('ignored', false)->where('is_bot', false)->where('last_seen_at', '>=', now()->subDays(7))->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Missing pages visited by people in the last 7 days';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('ignored', false))
            ->columns([
                TextColumn::make('path')->searchable()->limit(70)->copyable()
                    ->url(fn (NotFoundLog $record) => url($record->path), shouldOpenInNewTab: true),
                TextColumn::make('hits')->numeric()->sortable(),
                TextColumn::make('referrer')->limit(50)->placeholder('—')->toggleable()
                    ->url(fn (NotFoundLog $record) => $record->referrer, shouldOpenInNewTab: true),
                IconColumn::make('is_bot')->label('Bot')->boolean()->trueIcon(Heroicon::OutlinedCpuChip)->falseIcon(Heroicon::OutlinedUser)
                    ->trueColor('gray')->falseColor('primary'),
                TextColumn::make('last_seen_at')->label('Last seen')->since()->sortable(),
                TextColumn::make('first_seen_at')->label('First seen')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_bot')->label('Visitor')->trueLabel('Bots only')->falseLabel('People only'),
            ])
            ->recordActions([
                Action::make('redirect')->label('Create redirect')->icon(Heroicon::OutlinedArrowUturnRight)->color('primary')
                    ->modalHeading(fn (NotFoundLog $record) => 'Redirect '.$record->path)
                    ->schema(RedirectResource::formSchema(withFrom: false))
                    ->action(function (NotFoundLog $record, array $data) {
                        $status = (int) $data['status_code'];
                        $record->redirectTo($status === 410 ? null : $data['to_path'], $status === 410 ? 301 : $status);
                        Notification::make()->success()->title('Redirect created')->send();
                    }),
                Action::make('ignore')->label('Ignore')->icon(Heroicon::OutlinedEyeSlash)->color('gray')
                    ->action(fn (NotFoundLog $record) => $record->update(['ignored' => true])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('ignore')->label('Ignore')->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => NotFoundLog::whereKey($records->modelKeys())->update(['ignored' => true])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListNotFoundLogs::route('/')];
    }
}
