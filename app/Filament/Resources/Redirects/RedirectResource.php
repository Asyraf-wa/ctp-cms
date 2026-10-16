<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

// SEO-11: redirects from renamed slugs, the legacy site and by hand. Chains and loops are flattened on save.
class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'from_path';

    public const SOURCES = [Redirect::SOURCE_MANUAL => 'Manual', Redirect::SOURCE_SLUG => 'Slug change', Redirect::SOURCE_LEGACY => 'Legacy site'];

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::formSchema());
    }

    /** @return list<mixed> */
    public static function formSchema(bool $withFrom = true): array
    {
        return array_values(array_filter([
            $withFrom ? TextInput::make('from_path')->label('From')->required()->maxLength(500)->prefix(url('/'))
                ->placeholder('/old-tutorial-slug')
                ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                    if (preg_match('#^https?://#i', (string) $value)) {
                        $fail('Use the path only, e.g. /old-tutorial-slug.');
                    }
                }) : null,
            ToggleButtons::make('status_code')->label('Type')->inline()->required()->default(301)->live()
                ->options([301 => '301 Moved permanently', 302 => '302 Temporary', 410 => '410 Gone (deleted)']),
            TextInput::make('to_path')->label('To')->maxLength(500)
                ->placeholder('/new-tutorial-slug or https://…')
                ->required(fn (Get $get) => (int) $get('status_code') !== 410)
                ->visible(fn (Get $get) => (int) $get('status_code') !== 410)
                ->helperText('A path on this site, or a full URL for an external page.'),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('From')->searchable()->limit(60)->copyable(),
                TextColumn::make('to_path')->label('To')->searchable()->limit(60)->placeholder('— gone —'),
                TextColumn::make('status_code')->label('Type')->badge()
                    ->color(fn (int $state) => match ($state) {
                        410 => 'danger', 302 => 'warning', default => 'success'
                    }),
                TextColumn::make('source')->badge()->color('gray')->formatStateUsing(fn (string $state) => self::SOURCES[$state] ?? $state),
                TextColumn::make('hits')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label('Last used')->since()->sortable()->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('source')->options(self::SOURCES),
                SelectFilter::make('status_code')->label('Type')->options([301 => '301', 302 => '302', 410 => '410']),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Redirect $record, array $data) => self::save($data, $record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    /** @param  array<string, mixed>  $data */
    public static function save(array $data, ?Redirect $record = null): ?Redirect
    {
        $from = $data['from_path'] ?? $record?->from_path;
        if ($record && Redirect::normalizePath((string) $from) !== $record->from_path) {
            $record->delete();
        }
        $status = (int) $data['status_code'];

        return Redirect::record((string) $from, $status === 410 ? null : ($data['to_path'] ?? null), $record->source ?? Redirect::SOURCE_MANUAL, $status === 410 ? 301 : $status);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
