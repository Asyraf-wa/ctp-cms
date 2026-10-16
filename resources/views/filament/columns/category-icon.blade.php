@php($record = $getRecord())
<div class="flex px-3 text-gray-500" @if ($record->technology?->brandColor()) style="color: {{ $record->technology->brandColor() }}" @endif>
    @if ($record->technology)
        {{ $record->technology->iconHtml('size-5') }}
    @elseif ($getState())
        <x-dynamic-component :component="'lucide-'.$getState()" class="size-5" />
    @endif
</div>
