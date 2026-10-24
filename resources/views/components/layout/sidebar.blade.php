<aside id="app-sidebar" data-app-sidebar class="app-sidebar flex h-full shrink-0 flex-col border-r border-slate-200 bg-white">
    <a href="{{ route('dashboard') }}" class="sidebar-logo flex h-[76px] items-center gap-3 border-b border-slate-100 px-5" aria-label="Energiciency dashboard">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white">
            <img src="{{ asset('images/energiciency-logo.png') }}" alt="Energiciency" class="h-full w-full object-contain">
        </span>
        <span class="sidebar-brand-name min-w-0">
            <span class="block text-sm font-extrabold tracking-wide text-navy-900">ENERGICIENCY</span>
            <span class="block text-[10px] font-medium tracking-[0.13em] text-slate-500">ENERGY INTELLIGENCE</span>
        </span>
    </a>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Navigasi utama">
        @php
            $navigation = [
                ['title' => null, 'items' => [['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid']]],
                ['title' => 'Production', 'items' => [
                    ['route' => 'production-orders.index', 'label' => 'Production Orders', 'icon' => 'clipboard'],
                    ['route' => 'products.index', 'label' => 'Products', 'icon' => 'cube'],
                    ['route' => 'processes.index', 'label' => 'Processes', 'icon' => 'flow'],
                ]],
                ['title' => 'Asset management', 'items' => [
                    ['route' => 'machines.index', 'label' => 'Machines', 'icon' => 'machine'],
                    ['route' => 'maintenance-logs.index', 'label' => 'Maintenance', 'icon' => 'wrench'],
                ]],
                ['title' => 'Energy management', 'items' => [
                    ['route' => 'energy-records.index', 'label' => 'Energy monitoring', 'icon' => 'bolt'],
                    ['route' => 'costing', 'label' => 'Energy costing', 'icon' => 'currency'],
                ]],
                ['title' => null, 'items' => [['route' => 'inventory.index', 'label' => 'Inventory', 'icon' => 'box']]],
                ['title' => 'Analytics & reports', 'items' => [
                    ['route' => 'oee-records.index', 'label' => 'OEE records', 'icon' => 'chart'],
                    ['route' => 'reports', 'label' => 'Reports', 'icon' => 'document'],
                ]],
            ];
        @endphp

        @foreach ($navigation as $group)
            <div>
                @if ($group['title'])
                    <p class="sidebar-group-title mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">{{ $group['title'] }}</p>
                @endif
                <div class="space-y-1">
                    @foreach ($group['items'] as $item)
                        @php($active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])))
                        <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}" class="sidebar-link flex items-center gap-3 rounded-lg px-3 text-[13px] font-semibold transition {{ $active ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-navy-900' }}">
                            <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                @switch($item['icon'])
                                    @case('grid') <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/> @break
                                    @case('clipboard') <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5h6M9 10h6M9 14h6M9 18h3"/> @break
                                    @case('cube') <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 8 9 5 9-5M3 8v9l9 5 9-5V8M12 13v9"/> @break
                                    @case('layers') <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/> @break
                                    @case('flow') <rect x="3" y="4" width="7" height="6" rx="1"/><rect x="14" y="14" width="7" height="6" rx="1"/><path d="M10 7h4a3 3 0 0 1 3 3v4M14 17h-4a3 3 0 0 1-3-3v-4"/> @break
                                    @case('machine') <rect x="3" y="7" width="18" height="13" rx="2"/><path d="M7 7V4h10v3M7 12h3M7 16h3M15 12h2v4h-2z"/> @break
                                    @case('wrench') <path d="M14.5 6.5a5 5 0 0 0-6.7 6.7l-4.9 4.9a2 2 0 0 0 2.8 2.8l4.9-4.9a5 5 0 0 0 6.7-6.7l-3 3-3.5-3.5 3.7-2.3Z"/> @break
                                    @case('bolt') <path d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z"/> @break
                                    @case('currency') <circle cx="12" cy="12" r="9"/><path d="M16 8.5c-.7-.8-1.8-1.2-3.2-1.2-1.8 0-3 .9-3 2.2 0 3.5 6.5 1.3 6.5 4.8 0 1.4-1.3 2.4-3.3 2.4-1.5 0-2.8-.5-3.7-1.5M12 5v14"/> @break
                                    @case('box') <path d="m12 3 9 5v9l-9 5-9-5V8l9-5Z"/><path d="m3 8 9 5 9-5M12 13v9"/> @break
                                    @case('chart') <path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 6-7"/><circle cx="7" cy="15" r="1"/><circle cx="11" cy="11" r="1"/><circle cx="14" cy="13" r="1"/><circle cx="20" cy="6" r="1"/> @break
                                    @case('document') <path d="M6 3h8l5 5v13H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v6h5M8 13h8M8 17h8"/> @break
                                @endswitch
                            </svg>
                            <span class="sidebar-label truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="sidebar-user-copy min-w-0">
                <span class="block truncate text-xs font-semibold text-navy-900">{{ auth()->user()->name }}</span>
                <span class="block truncate text-[10px] text-slate-500">Workspace</span>
            </span>
        </div>
    </div>
</aside>
