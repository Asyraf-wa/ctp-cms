<?php

namespace App\Filament\Resources\Comments;

use App\Filament\Resources\Comments\Pages\ListComments;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Support\CommentNotifier;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use UnitEnum;

// CMT-03: moderation queue. Approve, spam, trash, edit, and reply as the author (Author badge).
class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $pending = Comment::where('status', Comment::PENDING)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Waiting for approval';
    }

    // ADM-02: comments in the admin's global search.
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'body'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(80),
            TextInput::make('email')->email()->required()->maxLength(190),
            Textarea::make('body')->required()->rows(8)->maxLength(5000)->columnSpanFull()
                ->helperText('Markdown-lite: inline code, code fences, bold, italic, links.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['article:id,title,slug,type', 'parent:id,name', 'user']))
            ->columns([
                ViewColumn::make('name')->label('Comment')->view('filament.comments.cell')->searchable(['name', 'email', 'body']),
                TextColumn::make('article.title')->label('On')->limit(40)->wrap()
                    ->url(fn (Comment $record) => $record->status === Comment::APPROVED ? $record->url() : url($record->article->publicPath() ?? '/'), shouldOpenInNewTab: true),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state) => Comment::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Comment::APPROVED => 'success', Comment::PENDING => 'warning', Comment::SPAM => 'danger', default => 'gray'
                    })
                    ->description(fn (Comment $record) => $record->spam_reason),
                TextColumn::make('created_at')->label('Received')->since()->sortable()
                    ->dateTimeTooltip(timezone: config('ctp.display_timezone')),
            ])
            ->filters([
                SelectFilter::make('article_id')->label('Article')->searchable()
                    ->options(fn () => Article::whereHas('comments')->orderBy('title')->pluck('title', 'id')->all()),
            ])
            ->recordActions([
                Action::make('approve')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Comment $record) => $record->status !== Comment::APPROVED)
                    ->action(fn (Comment $record) => self::approve($record)),
                Action::make('reply')->label('Reply')->icon(Heroicon::OutlinedArrowUturnLeft)->color('primary')
                    ->visible(fn (Comment $record) => in_array($record->status, [Comment::APPROVED, Comment::PENDING], true))
                    ->modalHeading(fn (Comment $record) => 'Reply to '.$record->name)
                    ->modalDescription(fn (Comment $record) => new HtmlString('<div class="prose prose-sm max-w-none dark:prose-invert">'.$record->body_html.'</div>'))
                    ->schema([
                        Textarea::make('body')->label('Your reply')->required()->rows(6)->maxLength(5000)
                            ->helperText('Posted as the author, with the Author badge. Approving the comment as well, if it is pending.'),
                    ])
                    ->action(fn (Comment $record, array $data) => self::reply($record, $data['body'])),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('spam')->label('Mark as spam')->icon(Heroicon::OutlinedShieldExclamation)->color('danger')
                        ->visible(fn (Comment $record) => $record->status !== Comment::SPAM)
                        ->action(fn (Comment $record) => $record->update(['status' => Comment::SPAM, 'spam_reason' => 'marked by hand'])),
                    Action::make('trash')->label('Move to trash')->icon(Heroicon::OutlinedArchiveBoxXMark)->color('gray')
                        ->visible(fn (Comment $record) => $record->status !== Comment::TRASH)
                        ->action(fn (Comment $record) => $record->update(['status' => Comment::TRASH])),
                    DeleteAction::make()->label('Delete for good'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')->icon(Heroicon::OutlinedCheck)->color('success')
                        ->action(function (Collection $records): void {
                            foreach ($records as $comment) {
                                /** @var Comment $comment */
                                self::approve($comment, quiet: true);
                            }
                        }),
                    BulkAction::make('spam')->label('Mark as spam')->icon(Heroicon::OutlinedShieldExclamation)->color('danger')
                        ->action(fn (Collection $records) => Comment::whereKey($records->modelKeys())->update(['status' => Comment::SPAM, 'spam_reason' => 'marked by hand'])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function approve(Comment $comment, bool $quiet = false): void
    {
        $wasApproved = $comment->status === Comment::APPROVED;
        $comment->update(['status' => Comment::APPROVED, 'spam_reason' => null]);
        if (! $wasApproved) {
            CommentNotifier::approved($comment);
        }
        if (! $quiet) {
            Notification::make()->success()->title('Comment approved')->send();
        }
    }

    // The reply goes under the top-level comment (one level deep) and is live straight away.
    public static function reply(Comment $comment, string $body): Comment
    {
        if ($comment->status !== Comment::APPROVED) {
            self::approve($comment, quiet: true);
        }
        /** @var User|null $user */
        $user = auth()->user();
        $reply = Comment::create([
            'article_id' => $comment->article_id,
            'parent_id' => $comment->parent_id ?? $comment->id,
            'user_id' => $user?->getKey(),
            'name' => $user ? $user->authorName() : 'Author',
            'email' => $user ? $user->email : 'author@localhost',
            'body' => $body,
            'status' => Comment::APPROVED,
        ]);
        CommentNotifier::approved($reply->load('parent'));
        Notification::make()->success()->title('Reply posted')->send();

        return $reply;
    }

    public static function getPages(): array
    {
        return ['index' => ListComments::route('/')];
    }
}
