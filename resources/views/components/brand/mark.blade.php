{{-- CTP brand mark: teal gradient tile with the owner's </> glyph in Victor Mono (its ligatures join the characters). --}}
@props(['class' => 'size-8'])
<span {{ $attributes->class([$class, 'flex aspect-square shrink-0 items-center justify-center rounded-[9px] bg-linear-135 from-[#14b8a6] to-[#115e59] text-white shadow-[0_4px_12px_-4px_rgb(13_148_136/0.6)] [container-type:size]']) }} aria-hidden="true">
    {{-- cqw: the glyph scales with the tile, whatever its size. --}}
    <span class="font-['Victor_Mono',monospace] text-[40cqw] leading-none font-bold tracking-tighter select-none">&lt;/&gt;</span>
</span>
