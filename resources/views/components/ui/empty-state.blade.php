@props(['title' => 'Belum ada data', 'description' => null])

<div {{ $attributes->merge(['class' => 'px-6 py-14 text-center']) }}>
    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700" aria-hidden="true">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg>
    </span>
    <h3 class="mt-4 text-sm font-bold text-navy-900">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
