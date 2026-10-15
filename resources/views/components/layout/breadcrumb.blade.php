@props(['items' => []])

<nav aria-label="Breadcrumb" class="mb-2">
    <ol class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
        @foreach ($items as $item)
            @if (! $loop->first)
                <li aria-hidden="true" class="text-slate-300">/</li>
            @endif
            <li>
                @if (! $loop->last && isset($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-brand-700">{{ $item['label'] }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" class="font-semibold text-slate-700" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
