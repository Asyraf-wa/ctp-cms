{{-- CTP brand mark: teal gradient tile with the owner's </> glyph in Victor Mono. --}}
@props(['class' => 'size-8'])
<span {{ $attributes->class([$class, 'flex aspect-square shrink-0 items-center justify-center rounded-[9px] bg-linear-135 from-[#14b8a6] to-[#115e59] text-white shadow-[0_4px_12px_-4px_rgb(13_148_136/0.6)] [container-type:size]']) }} aria-hidden="true">
    {{--
        Victor Mono's ligature joins </> into one glyph. Ligatures switch off with any letter-spacing or with
        the body's Inter feature settings (cv11, ss01), so both are reset here. cqw scales it with the tile.
    --}}
    <span class="leading-none select-none" style="font-family: 'Victor Mono', monospace; font-weight: 700; font-size: 44cqw; letter-spacing: 0; font-feature-settings: normal; font-variant-ligatures: common-ligatures contextual;">&lt;/&gt;</span>
</span>
