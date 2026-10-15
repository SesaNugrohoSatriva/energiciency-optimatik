@props(['title' => 'Langkah berikutnya', 'tone' => 'info'])

@php
    $classes = match ($tone) {
        'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
        default => 'border-brand-100 bg-brand-50 text-navy-900',
    };
@endphp

<section {{ $attributes->merge(['class' => 'rounded-xl border p-4 '.$classes]) }} aria-label="{{ $title }}">
    <h2 class="text-sm font-bold">{{ $title }}</h2>
    <div class="mt-1 text-sm leading-6">{{ $slot }}</div>
</section>
