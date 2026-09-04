{{--
    Ringkasan satu pengajuan perbaikan data.

    Dipakai di form penunjukan (saat kodenya sudah diketahui dari query string)
    dan di halaman detail. Bentuknya sengaja sama dengan yang digambar oleh JS
    dropdown di form: kalau keduanya berbeda, pengguna akan menyimpulkan yang
    dilihatnya sebelum menyimpan bukan yang tersimpan.

    $pengajuan: App\Models\PerbaikanData, relasi `target` sudah dimuat.
--}}
<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm">
    <div class="flex"><dt class="w-28 text-gray-500">Kode</dt><dd class="font-medium text-gray-800">{{ $pengajuan->kode_pengajuan }}</dd></div>
    <div class="flex"><dt class="w-28 text-gray-500">Status</dt><dd class="text-gray-800">{{ $pengajuan->status }}</dd></div>
    <div class="flex"><dt class="w-28 text-gray-500">Pengaju</dt><dd class="text-gray-800">{{ $pengajuan->pengaju ?: '-' }}</dd></div>
    <div class="flex"><dt class="w-28 text-gray-500">Tgl Pengajuan</dt><dd class="text-gray-800">{{ optional($pengajuan->tgl_pengajuan)->format('d/m/Y H:i') ?? '-' }}</dd></div>
    <div class="flex sm:col-span-2"><dt class="w-28 text-gray-500">Jenis</dt><dd class="text-gray-800">{{ $pengajuan->jenis ?: '-' }}</dd></div>
</dl>

@if($pengajuan->target->isNotEmpty())
    <div class="mt-3 border-t border-gray-200 pt-2">
        <p class="text-xs font-semibold text-gray-600 mb-1">Perubahan yang diminta</p>

        @foreach($pengajuan->target as $target)
            <div class="py-1 border-b last:border-b-0 border-gray-100">
                <div class="text-xs text-gray-700">{{ $target->labelModul() }} &middot; {{ $target->labelField() }}</div>
                {{-- Nilai ditampilkan mentah: memformat ulang angka bisa membuat
                     dua nilai yang berbeda tampil sama, dan justru salah ketik
                     titik atau nol yang paling sering dikoreksi di sini. --}}
                <div class="text-xs text-red-700 line-through break-all">{{ $target->nilai_lama ?? '(kosong)' }}</div>
                <div class="text-xs text-green-700 font-medium break-all">{{ $target->nilai_baru ?? '(kosong)' }}</div>
                @if($target->alasan)
                    <div class="text-xs text-gray-500">Alasan: {{ $target->alasan }}</div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <p class="mt-3 text-xs text-gray-500">
        Pengajuan ini tidak mencantumkan perubahan terstruktur — isinya hanya dokumen lampiran.
    </p>
@endif
