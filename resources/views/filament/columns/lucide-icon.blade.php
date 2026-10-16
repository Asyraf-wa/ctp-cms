@if ($getState())
    <div class="px-3 text-gray-500">
        <x-dynamic-component :component="'lucide-'.$getState()" class="size-5" />
    </div>
@endif
