{{-- Difficulty badge (ART-05). --}}
@props(['level'])
@if ($level)
    <span {{ $attributes->class([
        'inline-flex items-center rounded-xs px-2 py-0.5 text-xs font-semibold',
        'bg-[#dcfce7] text-beginner dark:bg-[#14532d]/40' => $level->value === 'beginner',
        'bg-[#fef9c3] text-intermediate dark:bg-[#713f12]/40' => $level->value === 'intermediate',
        'bg-[#fee2e2] text-advanced dark:bg-[#7f1d1d]/40' => $level->value === 'advanced',
    ]) }}>{{ $level->getLabel() }}</span>
@endif
