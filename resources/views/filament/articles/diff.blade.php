{{-- Revision vs current content (ART-03). --}}
@if (empty($diff))
    <p class="text-sm text-gray-500">Choose a revision to compare it with the current content.</p>
@elseif (collect($diff)->every(fn ($l) => $l[0] === '='))
    <p class="text-sm text-gray-500">This revision is identical to the current content.</p>
@else
    <div class="max-h-[60vh] overflow-auto rounded-lg border border-gray-200 font-mono text-xs leading-relaxed dark:border-white/10">
        @foreach ($diff as [$op, $line])
            <div @class([
                'whitespace-pre-wrap px-3',
                'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $op === '-',
                'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $op === '+',
                'text-gray-600 dark:text-gray-400' => $op === '=',
            ])>{{ $op === '=' ? '  ' : $op.' ' }}{{ $line }}</div>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-gray-500"><span class="text-danger-600">−</span> only in the revision · <span class="text-success-600">+</span> only in the current content</p>
@endif
