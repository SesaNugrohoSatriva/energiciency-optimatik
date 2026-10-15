@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'error' => 'border-rose-200 bg-rose-50 text-rose-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'info' => 'border-brand-200 bg-brand-50 text-brand-900',
    ];
    $icons = ['success' => '✓', 'error' => '!', 'warning' => '!', 'info' => 'i'];
@endphp

<div {{ $attributes->merge(['class' => 'flex gap-3 rounded-xl border p-4 text-sm '.$styles[$type]]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">
    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-current text-xs font-bold" aria-hidden="true">{{ $icons[$type] }}</span>
    <div class="min-w-0">{{ $slot }}</div>
</div>
