<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Masuk · Energiciency</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-surface px-4 py-10">
        <main class="grid w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:grid-cols-2">
            <section class="flex flex-col justify-between bg-navy-950 p-8 text-white sm:p-10">
                <div>
                    <div class="inline-flex rounded-xl bg-white p-2">
                        <img src="{{ asset('images/energiciency-logo.png') }}" alt="Logo Energiciency" class="h-20 w-40 object-contain">
                    </div>
                    <p class="mt-10 text-xs font-bold uppercase tracking-[0.2em] text-brand-200">Energy intelligence for manufacturing</p>
                    <h1 class="mt-3 max-w-sm text-3xl font-bold leading-tight sm:text-4xl">Operasi yang lebih efisien dimulai dari data yang jelas.</h1>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-slate-300">Kelola produksi, aset, energi, dan biaya dalam satu workspace operasional.</p>
                </div>
                <p class="mt-12 text-xs text-slate-400">Energiciency · Workspace operasi produksi</p>
            </section>

            <section class="p-6 sm:p-10">
                <div class="mx-auto max-w-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-700">Selamat datang kembali</p>
                    <h2 class="mt-2 text-2xl font-bold text-navy-900">Masuk ke workspace</h2>
                    <p class="mt-2 text-sm text-slate-500">Gunakan akun Anda untuk melanjutkan.</p>

                    @if ($errors->any())
                        <x-ui.alert type="error" class="mt-5">{{ $errors->first() }}</x-ui.alert>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
                        @csrf
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-navy-900">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="app-input @error('email') border-rose-500 @enderror" placeholder="nama@perusahaan.com" aria-invalid="@error('email') true @else false @enderror">
                            @error('email')<p class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-semibold text-navy-900">Kata sandi</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required class="app-input @error('password') border-rose-500 @enderror" placeholder="Masukkan kata sandi">
                            @error('password')<p class="mt-1.5 text-xs font-medium text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <label class="flex min-h-8 items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Ingat saya
                        </label>
                        <button type="submit" class="app-button app-button-primary min-h-11 w-full">Masuk</button>
                    </form>

                    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs leading-5 text-slate-500">
                        Akun demo setelah menjalankan seeder:<br>
                        <strong class="text-slate-700">admin@energiciency.local</strong> / <strong class="text-slate-700">password</strong>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
