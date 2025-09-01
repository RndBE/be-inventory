<div class="p-6">
    <div class="bg-white shadow rounded-lg p-6 border mb-6">
        <h2 class="text-xl font-bold text-gray-700 mb-4">
            Detail Produk Jadi
        </h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-gray-600">Kode List</p>
                <p class="font-semibold">{{ $list->kode_list }}</p>
            </div>
            <div>
                <p class="text-gray-600">Kode Produksi</p>
                <p class="font-semibold">{{ $list->produksi->kode_produksi ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-600">Tanggal Masuk Gudang</p>
                <p class="font-semibold">
                    {{ $list->tanggal_masuk_gudang ? \Carbon\Carbon::parse($list->tanggal_masuk_gudang)->format('Y-m-d H:i:s') : '-' }}
                </p>
            </div>
            <div>
                <p class="text-gray-600">Serial Number</p>
                <p class="font-semibold">{{ $list->serial_number ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- QC 1 --}}
    <div class="bg-white shadow rounded-lg p-6 border mb-6">
        <h3 class="text-lg font-bold text-gray-700 mb-4">QC 1</h3>
        @if($list->qc1)
            <div class="grid grid-cols-2 gap-4">
                <div>
                    {{-- <p class="text-gray-600">Grade</p> --}}
                    {{-- <p class="font-semibold">{{ $list->qc1->grade }}</p> --}}
                    @if($list->qc1->grade === 'A')
                        <img src="{{ asset('images/grade A.png') }}" alt="Grade A" class="h-16 inline">
                    @elseif($list->qc1->grade === 'B')
                        <img src="{{ asset('images/grade B.png') }}" alt="Grade B" class="h-16 inline">
                    @endif
                </div>
                <div>
                    <p class="text-gray-600">Petugas</p>
                    <p class="font-semibold">{{ $list->petugas_produksi ?? '-' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-600">Keterangan</p>
                    <p class="font-semibold">{{ $list->qc1->keterangan ?? '-' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-600">Laporan QC</p>
                    @if($list->qc1 && $list->qc1->laporan_qc)
                        @php
                            $path = Storage::url($list->qc1->laporan_qc);
                            $ext = pathinfo($path, PATHINFO_EXTENSION);
                        @endphp

                        @if(in_array(strtolower($ext), ['jpg','jpeg','png','gif']))
                            <img src="{{ $path }}"
                                alt="Laporan QC"
                                class="w-full max-h-[600px] object-contain border rounded">
                        @elseif(strtolower($ext) === 'pdf')
                            <iframe src="{{ $path }}" class="w-full h-screen border rounded"></iframe>
                        @else
                            <a href="{{ $path }}" target="_blank" class="text-blue-600 underline">Lihat Laporan</a>
                        @endif
                    @else
                        <span>-</span>
                    @endif
                </div>

                <div class="col-span-2">
                    <p class="text-gray-600 mb-2">Dokumentasi</p>
                    <div class="flex gap-3 flex-wrap">
                        @foreach($list->qc1->dokumentasi as $doc)
                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank">
                                <img src="{{ Storage::url($doc->file_path) }}" class="h-24 w-24 object-cover rounded border">
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <p class="text-gray-500">Belum ada data QC 1</p>
        @endif
    </div>

    {{-- QC 2 --}}
    <div class="bg-white shadow rounded-lg p-6 border mb-6">
        <h3 class="text-lg font-bold text-gray-700 mb-4">QC 2</h3>
        @if($list->qc2)
            <div class="grid grid-cols-2 gap-4">
                <div>
                    {{-- <p class="text-gray-600">Grade</p>
                    <p class="font-semibold">{{ $list->qc2->grade }}</p> --}}
                    @if($list->qc2->grade === 'A')
                        <img src="{{ asset('images/grade A.png') }}" alt="Grade A" class="h-16 inline">
                    @elseif($list->qc2->grade === 'B')
                        <img src="{{ asset('images/grade B.png') }}" alt="Grade B" class="h-16 inline">
                    @endif
                </div>
                <div>
                    <p class="text-gray-600">Petugas</p>
                    <p class="font-semibold">{{ $list->qc2->petugas->name ?? '-' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-600">Keterangan</p>
                    <p class="font-semibold">{{ $list->qc2->keterangan ?? '-' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-600">Laporan QC</p>
                    {{-- @if($list->qc2->laporan_qc)
                        <a href="{{ Storage::url($list->qc2->laporan_qc) }}" target="_blank" class="text-blue-600 underline">
                            Lihat Laporan
                        </a>
                    @else
                        <span>-</span>
                    @endif --}}
                    @if($list->qc2 && $list->qc2->laporan_qc)
                        @php
                            $path = Storage::url($list->qc2->laporan_qc);
                            $ext = pathinfo($path, PATHINFO_EXTENSION);
                        @endphp

                        @if(in_array(strtolower($ext), ['jpg','jpeg','png','gif']))
                            <img src="{{ $path }}"
                                alt="Laporan QC"
                                class="w-full max-h-[600px] object-contain border rounded">
                        @elseif(strtolower($ext) === 'pdf')
                            <iframe src="{{ $path }}" class="w-full h-screen border rounded"></iframe>
                        @else
                            <a href="{{ $path }}" target="_blank" class="text-blue-600 underline">Lihat Laporan</a>
                        @endif
                    @else
                        <span>-</span>
                    @endif
                </div>
                <div class="col-span-2">
                    <p class="text-gray-600 mb-2">Dokumentasi</p>
                    <div class="flex gap-3 flex-wrap">
                        @foreach($list->qc2->dokumentasi as $doc)
                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank">
                                <img src="{{ Storage::url($doc->file_path) }}" class="h-24 w-24 object-cover rounded border">
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <p class="text-gray-500">Belum ada data QC 2</p>
        @endif
    </div>
</div>
