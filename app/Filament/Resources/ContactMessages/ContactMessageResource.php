<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactReply;
use App\Support\Html;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use UnitEnum;

// CONTACT-06: the inbox. New messages are bold; opening one marks it read; replies are emailed and kept.
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Inbox';

    protected static ?string $modelLabel = 'message';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = ContactMessage::where('status', ContactMessage::NEW)->count();

        return $new ? (string) $new : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Unread messages';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()->columnSpan(2)->schema([
                TextEntry::make('message')->hiddenLabel()->prose()
                    ->formatStateUsing(fn (string $state) => new HtmlString(nl2br(e($state)))),
                TextEntry::make('replies')->label('Replies')
                    ->state(fn (ContactMessage $record) => $record->replies->isEmpty() ? null : new HtmlString($record->replies->map(fn (ContactReply $r) => '<div style="margin-bottom:12px;padding:10px 12px;border-left:3px solid #14b8a6;background:rgb(20 184 166 / .08)"><p style="font-size:12px;opacity:.7">'
                        .e($r->user?->authorName() ?? 'You').' · '.e($r->created_at->timezone(config('ctp.display_timezone'))->format('j M Y, g:i a')).'</p>'.Html::comment($r->body).'</div>')->join('')))
                    ->visible(fn (ContactMessage $record) => $record->replies->isNotEmpty()),
            ]),
            Section::make()->columnSpan(1)->schema([
                TextEntry::make('name')->weight('bold'),
                TextEntry::make('email')->copyable()->url(fn (ContactMessage $record) => 'mailto:'.$record->email),
                TextEntry::make('topic')->badge()->formatStateUsing(fn (ContactMessage $record) => $record->topicLabel()),
                TextEntry::make('company')->placeholder('—')->visible(fn (ContactMessage $record) => filled($record->company)),
                TextEntry::make('article.title')->label('About tutorial')
                    ->url(fn (ContactMessage $record) => $record->article ? url($record->article->publicPath()) : null, shouldOpenInNewTab: true)
                    ->visible(fn (ContactMessage $record) => $record->article_id !== null),
                TextEntry::make('status')->badge()->formatStateUsing(fn (string $state) => ContactMessage::STATUSES[$state] ?? $state)->color(fn (string $state) => self::statusColor($state)),
                TextEntry::make('created_at')->label('Received')->dateTime('j M Y, g:i a', config('ctp.display_timezone')),
                TextEntry::make('consented_at')->label('Privacy consent')->dateTime('j M Y, g:i a', config('ctp.display_timezone')),
                TextEntry::make('spam_reason')->label('Spam reason')->visible(fn (ContactMessage $record) => filled($record->spam_reason)),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (ContactMessage $record) => self::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->searchable(['name', 'email', 'message'])
                    ->weight(fn (ContactMessage $record) => $record->status === ContactMessage::NEW ? 'bold' : 'normal')
                    ->description(fn (ContactMessage $record) => $record->email),
                TextColumn::make('message')->limit(80)->wrap()->color('gray')
                    ->weight(fn (ContactMessage $record) => $record->status === ContactMessage::NEW ? 'semibold' : 'normal'),
                TextColumn::make('topic')->badge()->color('gray')->formatStateUsing(fn (ContactMessage $record) => $record->topicLabel()),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state) => ContactMessage::STATUSES[$state] ?? $state)->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('created_at')->label('Received')->since()->sortable()->dateTimeTooltip(timezone: config('ctp.display_timezone')),
            ])
            ->filters([
                SelectFilter::make('topic')->options(collect(ContactMessage::TOPICS)->map(fn ($t) => $t['label'])->all()),
            ])
            ->recordActions([
                ViewAction::make()->label('Open'),
                ActionGroup::make([
                    self::archiveAction(),
                    self::spamAction(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('archive')->icon(Heroicon::OutlinedArchiveBox)
                        ->action(fn (Collection $records) => ContactMessage::whereKey($records->modelKeys())->update(['status' => ContactMessage::ARCHIVED])),
                    BulkAction::make('spam')->label('Mark as spam')->icon(Heroicon::OutlinedShieldExclamation)->color('danger')
                        ->action(fn (Collection $records) => ContactMessage::whereKey($records->modelKeys())->update(['status' => ContactMessage::SPAM, 'spam_reason' => 'marked by hand'])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function replyAction(): Action
    {
        return Action::make('reply')->label('Reply')->icon(Heroicon::OutlinedPaperAirplane)->color('primary')
            ->modalHeading(fn (ContactMessage $record) => 'Reply to '.$record->name)
            ->modalDescription(fn (ContactMessage $record) => 'Sent to '.$record->email.' from the site address. Their reply comes back to your contact email.')
            ->modalSubmitActionLabel('Send reply')
            ->schema([
                Textarea::make('body')->label('Message')->required()->rows(10)->maxLength(10000)
                    ->default(fn (ContactMessage $record) => 'Hi '.strtok($record->name, ' ').",\n\n")
                    ->helperText('Plain text; **bold**, `code` and links work. Their message is quoted below your reply.'),
            ])
            ->action(function (ContactMessage $record, array $data): void {
                $reply = $record->replies()->create(['user_id' => auth()->id(), 'body' => $data['body']]);
                Mail::to($record->email, $record->name)->queue(new ContactReplyMail($reply));
                $record->update(['status' => ContactMessage::REPLIED, 'replied_at' => now(), 'read_at' => $record->read_at ?? now()]);
                $record->load('replies.user'); // show the reply on the page straight away
                Notification::make()->success()->title('Reply sent to '.$record->email)->send();
            });
    }

    public static function archiveAction(): Action
    {
        return Action::make('archive')->icon(Heroicon::OutlinedArchiveBox)->color('gray')
            ->visible(fn (ContactMessage $record) => $record->status !== ContactMessage::ARCHIVED)
            ->action(fn (ContactMessage $record) => $record->update(['status' => ContactMessage::ARCHIVED]));
    }

    public static function spamAction(): Action
    {
        return Action::make('spam')->label('Mark as spam')->icon(Heroicon::OutlinedShieldExclamation)->color('danger')
            ->visible(fn (ContactMessage $record) => $record->status !== ContactMessage::SPAM)
            ->action(fn (ContactMessage $record) => $record->update(['status' => ContactMessage::SPAM, 'spam_reason' => 'marked by hand']));
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            ContactMessage::NEW => 'primary',
            ContactMessage::REPLIED => 'success',
            ContactMessage::SPAM => 'danger',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
