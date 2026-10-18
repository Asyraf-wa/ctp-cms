{{-- CTP brand mark: the </> glyph as one stroked line (the outline of Victor Mono's ligature), no font needed.
    stroke-width is the weight: 190 at w-10 (40px) matches Inter Bold stems next to the site name.
    The viewBox is cut at the glyph's top and bottom, which gives the flat tips; x has room for the mitred corners. --}}
@props(['class' => 'w-10'])
<svg {{ $attributes->class([$class, 'h-auto shrink-0 text-primary']) }} viewBox="-72 0 1850 1054" fill="none" stroke="currentColor" stroke-width="190" stroke-linejoin="miter" stroke-linecap="butt" aria-hidden="true" focusable="false">
    <polyline points="469.5,177 72.5,524 664,1041 1052,16 1633.5,524 1236.5,871" />
</svg>
