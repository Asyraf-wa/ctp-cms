<?php

namespace App\Filament\Forms\Components;

use App\Models\Media;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

// Thumbnail grid of library items; the state is the selected media id (used inside MediaPicker's modal).
class MediaGrid extends Field
{
    protected string $view = 'filament.forms.components.media-grid';

    protected ?Closure $modifyQueryUsing = null;

    public const LIMIT = 48;

    public function query(?Closure $callback): static
    {
        $this->modifyQueryUsing = $callback;

        return $this;
    }

    /** @return Collection<int, Media> */
    public function getItems(): Collection
    {
        $query = Media::query()->latest();

        if ($this->modifyQueryUsing) {
            $query = $this->evaluate($this->modifyQueryUsing, ['query' => $query], [Builder::class => $query]) ?? $query;
        }

        return $query->limit(self::LIMIT)->get();
    }
}
