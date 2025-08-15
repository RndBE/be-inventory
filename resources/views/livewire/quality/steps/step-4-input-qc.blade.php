<div class="bg-white shadow-md p-6 border rounded-md text-sm">

    <!-- Checkbox konfirmasi -->
    <div class="mb-4">
        <label class="flex items-start space-x-2">
            <input type="checkbox" wire:model="qc_confirmation" class="mt-1 form-check-input" />
            <span class="text-gray-700">
                Saya menyatakan bahwa semua data QC bahan masuk telah diperiksa dan valid.
            </span>
        </label>
        @error('qc_confirmation')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- (Optional) Catatan tambahan -->
    <div class="mb-4">
        <label for="qc_notes" class="block font-medium mb-1">Catatan Tambahan (opsional)</label>
        <textarea wire:model.defer="qc_notes" id="qc_notes" rows="3"
            class="form-control w-full resize-none" placeholder="Tulis jika ada catatan tambahan..."></textarea>
        @error('qc_notes')
            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Info Petugas -->
    <div class="mt-6 text-sm text-gray-600">
        <p>Petugas QC: <span class="font-semibold">{{ auth()->user()->name ?? '-' }}</span></p>
        <p>Tanggal: <span class="font-semibold">{{ now()->format('d-m-Y H:i') }}</span></p>
    </div>
</div>
