<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex min-h-[72px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" data-mobile-nav-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Buka navigasi" class="app-button app-button-secondary h-10 w-10 shrink-0 p-0 md:hidden">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <button type="button" data-sidebar-collapse aria-label="Ciutkan sidebar" aria-pressed="false" aria-controls="app-sidebar" class="app-button app-button-secondary hidden h-10 w-10 shrink-0 p-0 md:inline-flex">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16v14H4zM9 5v14M14 9l-2 3 2 3"/></svg>
            </button>
            <div class="min-w-0">
                <x-layout.breadcrumb :items="$breadcrumbs" />
                <h1 class="truncate text-lg font-bold leading-tight text-navy-900 sm:text-xl">{{ $pageTitle }}</h1>
                @if ($pageDescription)
                    <p class="mt-1 hidden truncate text-xs text-slate-500 sm:block">{{ $pageDescription }}</p>
                @endif
            </div>
        </div>

        <details class="group relative shrink-0">
            <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-lg px-2 hover:bg-slate-50 focus-visible:outline-brand-500">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden text-left sm:block">
                    <span class="block max-w-36 truncate text-xs font-semibold text-navy-900">{{ auth()->user()->name }}</span>
                    <span class="block text-[10px] text-slate-500">Signed in</span>
                </span>
                <svg class="hidden h-4 w-4 text-slate-500 sm:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.09 1.03l-4.25 4.51a.75.75 0 0 1-1.09 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="absolute right-0 top-full z-40 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                <p class="truncate px-3 py-2 text-xs text-slate-500">{{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </details>
    </div>
</header>
