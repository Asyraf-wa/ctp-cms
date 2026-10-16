<?php

namespace App\Filament\Widgets;

use App\Enums\ArticleStatus;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use App\Models\Article;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\NotFoundLog;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

// ADM-01: what needs attention, at a glance. Each tile opens the list behind it.
class SiteOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $published = Article::published()->count();
        $drafts = Article::where('status', ArticleStatus::Draft)->count();
        $scheduled = Article::where('status', ArticleStatus::Scheduled)->count();
        $pending = Comment::where('status', Comment::PENDING)->count();
        $unread = ContactMessage::where('status', ContactMessage::NEW)->count();
        $missing = NotFoundLog::where('ignored', false)->where('is_bot', false)->where('last_seen_at', '>=', now()->subDays(7))->count();
        $views = (int) DB::table('article_view_stats')->where('date', '>=', now()->subDays(7)->toDateString())->sum('views')
            + DB::table('article_views')->where('viewed_on', now()->toDateString())->count();

        return [
            Stat::make('Published', number_format($published))
                ->description(number_format($views).' views in the last 7 days')->icon(Heroicon::OutlinedDocumentText)
                ->url(ArticleResource::getUrl()),
            Stat::make('Drafts · scheduled', $drafts.' · '.$scheduled)
                ->description($scheduled ? 'Next: '.Article::where('status', ArticleStatus::Scheduled)->orderBy('published_at')->value('title') : 'Nothing scheduled')
                ->icon(Heroicon::OutlinedPencilSquare)->url(ArticleResource::getUrl('index', ['tab' => 'drafts'])),
            Stat::make('Comments waiting', $pending)->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color($pending ? 'warning' : 'gray')->description($pending ? 'Approve or reply' : 'All caught up')
                ->url(CommentResource::getUrl('index', ['tab' => Comment::PENDING])),
            Stat::make('Unread messages', $unread)->icon(Heroicon::OutlinedInbox)
                ->color($unread ? 'primary' : 'gray')->description($unread ? 'From the contact page' : 'Inbox is clear')
                ->url(ContactMessageResource::getUrl('index', ['tab' => 'unread'])),
            Stat::make('Missing pages', $missing)->icon(Heroicon::OutlinedExclamationTriangle)
                ->color($missing ? 'danger' : 'gray')->description('404s visited by people, 7 days')
                ->url(NotFoundLogResource::getUrl()),
        ];
    }
}
