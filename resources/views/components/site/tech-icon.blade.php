{{--
    A technology logo: grey at rest, brand colour when it or its .group/.tech-hover parent is hovered
    (lightened for dark mode by Technology::darkColor()). Decorative unless $label is given.
--}}
@props(['technology', 'size' => 'size-4', 'label' => null])
@if ($technology)
    <span {{ $attributes->class(['tech-icon inline-flex shrink-0 items-center justify-center', 'tech-icon--img' => $technology->icon_type === 'media']) }}
        @style(['--tech: '.$technology->brandColor() => $technology->brandColor(), '--tech-dark: '.$technology->darkColor() => $technology->darkColor()])
        @if ($label) role="img" aria-label="{{ $label }}" title="{{ $label }}" @endif>{{ $technology->iconHtml($size) }}</span>
@endif
