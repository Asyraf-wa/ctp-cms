@props(['status'])
<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-xs px-2 py-0.5 text-xs font-semibold',
    'bg-[#dcfce7] text-beginner dark:bg-[#14532d]/40' => $status->value === 'active',
    'bg-brand-soft text-brand-soft-foreground' => $status->value === 'maintained',
    'bg-muted text-muted-foreground' => $status->value === 'archived',
]) }}>{{ $status->getLabel() }}</span>
