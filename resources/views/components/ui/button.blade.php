@props(['href' => null, 'variant' => 'primary', 'type' => 'button'])

@php($classes = 'app-button '.($variant === 'secondary' ? 'app-button-secondary' : 'app-button-primary'))

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
