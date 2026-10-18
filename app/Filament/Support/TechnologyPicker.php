<?php

namespace App\Filament\Support;

use App\Filament\Resources\Technologies\TechnologyResource;
use App\Models\Technology;
use Filament\Forms\Components\Select;

// Select technologies with their logos; new ones can be added inline (tech stack, categories, projects).
class TechnologyPicker
{
    public static function make(string $name = 'technology_id', bool $creatable = true): Select
    {
        $select = Select::make($name)
            ->searchable()
            ->preload()
            ->allowHtml()
            ->options(fn () => self::options())
            ->getOptionLabelUsing(fn ($value) => ($t = Technology::find($value)) ? self::label($t) : null);

        return $creatable
            ? $select->createOptionForm(TechnologyResource::formSchema())->createOptionUsing(fn (array $data) => Technology::create($data)->getKey())
            : $select;
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return Technology::query()->with('iconMedia')->orderBy('name')->get()
            ->mapWithKeys(fn (Technology $t) => [$t->id => self::label($t)])->all();
    }

    public static function label(Technology $technology): string
    {
        return '<span style="display:inline-flex;align-items:center;gap:.5rem">'
            .'<span style="display:inline-flex;color:'.e($technology->brandColor() ?? 'currentColor').'">'.$technology->iconHtml('').'</span>'
            .e($technology->name).'</span>';
    }
}
