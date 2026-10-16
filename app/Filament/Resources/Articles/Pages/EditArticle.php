<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Content\Revisions;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use App\Support\TextDiff;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\URL;

/**
 * @property-read Article $record
 */
class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    public ?string $lastAutosavedAt = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($autosave = app(Revisions::class)->pendingAutosave($this->record, $this->user())) {
            Notification::make()
                ->warning()
                ->title('Unsaved changes found')
                ->body('An autosave from '.$autosave->updated_at->timezone(config('ctp.display_timezone'))->format('j M, g:i a').' is newer than this article. Use “Restore autosave” to bring it back.')
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('Preview')->icon(Heroicon::OutlinedEye)->color('gray')
                ->url(fn () => URL::temporarySignedRoute('articles.preview', now()->addDay(), ['article' => $this->record]), shouldOpenInNewTab: true),
            Action::make('view')->label('View live')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => url($this->record->publicPath() ?? '/'), shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->isPubliclyVisible()),
            Action::make('restoreAutosave')->label('Restore autosave')->icon(Heroicon::OutlinedArrowUturnLeft)->color('warning')
                ->visible(fn () => app(Revisions::class)->pendingAutosave($this->record, $this->user()) !== null)
                ->requiresConfirmation()
                ->modalDescription('Puts the autosaved title, excerpt, takeaways and content back into the editor. Nothing is saved until you click Save.')
                ->action(function () {
                    $autosave = app(Revisions::class)->pendingAutosave($this->record, $this->user());
                    if ($autosave) {
                        $this->loadRevision($autosave);
                    }
                }),
            $this->revisionsAction(),
            ActionGroup::make([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]),
        ];
    }

    private function revisionsAction(): Action
    {
        return Action::make('revisions')->label('Revisions')->icon(Heroicon::OutlinedClock)->color('gray')
            ->slideOver()
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Load into editor')
            ->schema([
                Select::make('revision_id')->label('Revision')->required()->live()
                    ->options(fn () => $this->record->revisions()->with('user')->limit(ArticleRevision::KEEP + 5)->get()
                        ->mapWithKeys(fn (ArticleRevision $r) => [$r->id => $r->created_at->timezone(config('ctp.display_timezone'))->format('j M Y, g:i a')
                            .' · '.($r->user?->authorName() ?? 'Unknown').($r->is_autosave ? ' · autosave' : '')])),
                View::make('filament.articles.diff')->viewData(fn (Get $get) => ['diff' => $this->diffAgainst($get('revision_id'))]),
            ])
            ->action(function (array $data) {
                $this->loadRevision(ArticleRevision::findOrFail($data['revision_id']));
            });
    }

    /** @return list<array{0: string, 1: string}> */
    private function diffAgainst(mixed $revisionId): array
    {
        $revision = $revisionId ? $this->record->revisions()->find($revisionId) : null;
        if (! $revision) {
            return [];
        }

        $current = $this->data ?? [];

        return TextDiff::lines(
            Revisions::text($revision->title, $revision->body),
            Revisions::text($current['title'] ?? '', is_array($current['body'] ?? null) ? $current['body'] : null),
        );
    }

    private function loadRevision(ArticleRevision $revision): void
    {
        $this->form->fill([
            ...$this->form->getRawState(),
            'title' => $revision->title,
            'excerpt' => $revision->excerpt,
            'tldr' => $revision->tldr ?? [],
            'body' => $revision->body,
        ]);

        Notification::make()->success()->title('Revision loaded')->body('Review it, then click Save to keep it.')->send();
    }

    // Polled every 30 s by the footer view (ART-03).
    public function autosave(): void
    {
        $state = $this->form->getRawState();

        if (app(Revisions::class)->autosave($this->record, $this->user(), $state)) {
            $this->lastAutosavedAt = now()->timezone(config('ctp.display_timezone'))->format('g:i a');
        }
    }

    public function getFooter(): ?ViewContract
    {
        return view('filament.articles.autosave', ['lastAutosavedAt' => $this->lastAutosavedAt]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ART-07: only meaningful updates move "Last updated".
        if ($this->data['meaningful_update'] ?? false) {
            $data['content_updated_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(Revisions::class)->record($this->record, $this->user());
        $this->data['meaningful_update'] = false;
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
