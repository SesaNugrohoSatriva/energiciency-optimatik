@props(['action', 'label', 'identifier'])

@php($dialogId = 'confirm-delete-'.$identifier)

<button type="button" data-confirm-open="{{ $dialogId }}" aria-haspopup="dialog" class="rounded-md px-2.5 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">
    Hapus<span class="sr-only"> {{ $label }} {{ $identifier }}</span>
</button>

<dialog id="{{ $dialogId }}" aria-labelledby="{{ $dialogId }}-title" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 p-0 text-navy-900 shadow-xl backdrop:bg-navy-950/40">
    <div class="p-5 sm:p-6">
        <h2 id="{{ $dialogId }}-title" class="text-base font-bold">Hapus {{ $label }}?</h2>
        <p class="mt-2 text-sm text-slate-600">Penghapusan akan diproses sesuai relasi dan aturan data aplikasi. Beberapa data yang masih digunakan mungkin tidak dapat dihapus. Tindakan yang berhasil tidak dapat dibatalkan.</p>
        <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row">
            <button type="button" data-confirm-close class="app-button app-button-secondary">Batal</button>
            <form method="POST" action="{{ $action }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="app-button w-full bg-rose-700 text-white hover:bg-rose-800 sm:w-auto">Hapus data</button>
            </form>
        </div>
    </div>
</dialog>
