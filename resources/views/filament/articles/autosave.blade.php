{{-- ART-03: save unsaved work every 30 seconds (as a revision, never to the live article). --}}
<div wire:poll.30s.keep-alive="autosave" class="text-xs text-gray-400" aria-live="polite">
    @if ($lastAutosavedAt)
        Autosaved at {{ $lastAutosavedAt }}
    @endif
</div>
