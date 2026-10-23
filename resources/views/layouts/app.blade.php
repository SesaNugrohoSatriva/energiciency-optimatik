<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $pageTitle ?? 'Energiciency' }} · Energiciency</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div data-app-shell class="app-shell flex min-h-screen">
            <button type="button" data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-navy-950/40 md:hidden" aria-label="Tutup navigasi"></button>
            <x-layout.sidebar />

            <div class="app-main min-w-0 flex-1">
                <x-layout.header :page-title="$pageTitle ?? 'Energiciency'" :page-description="$pageDescription ?? null" :breadcrumbs="$breadcrumbs ?? []" />

                <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8" id="main-content">
                    @if (session('success'))
                        <x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>
                    @endif
                    @if (session('error'))
                        <x-ui.alert type="error" class="mb-5">{{ session('error') }}</x-ui.alert>
                    @endif
                    @if ($errors->any())
                        <x-ui.alert type="error" class="mb-5">
                            <p class="font-semibold">Periksa kembali data yang dimasukkan:</p>
                            <ul class="mt-1 list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>
        @stack('modals')
    </body>
</html>
