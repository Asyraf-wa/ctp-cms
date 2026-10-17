{{-- CTP brand mark: teal </> glyph in Victor Mono. --}}
@props(['class' => 'size-8'])
<span {{ $attributes->class([$class, 'flex aspect-square shrink-0 items-center justify-center text-primary [container-type:size]']) }} aria-hidden="true">
    {{--
        Victor Mono's ligature joins </> into one glyph. Ligatures switch off with any letter-spacing or with
        the body's Inter feature settings (cv11, ss01), so both are reset here. cqw scales it with the tile.
    --}}
    <span class="leading-none select-none" style="font-family: 'Victor Mono', monospace; font-weight: 700; font-size: 70cqw; letter-spacing: 0; font-feature-settings: normal; font-variant-ligatures: common-ligatures contextual;">&lt;/&gt;</span>
</span>
