@props(['label', 'value', 'detail' => null, 'icon' => '•', 'tone' => 'blue'])

@php
    $tones = [
        'blue' => 'bg-brand-50 text-brand-700',
        'green' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'red' => 'bg-rose-50 text-rose-700',
        'navy' => 'bg-slate-100 text-navy-800',
    ];
@endphp

<article class="app-card min-w-0 p-4 sm:p-5">
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold text-slate-500 sm:text-sm">{{ $label }}</p>
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold {{ $tones[$tone] }}" aria-hidden="true">{{ $icon }}</span>
    </div>
    <p class="mt-4 truncate text-2xl font-extrabold tracking-tight text-navy-900 sm:text-[28px]">{{ $value }}</p>
    @if ($detail)
        <p class="mt-1 truncate text-xs text-slate-500">{{ $detail }}</p>
    @endif
</article>
