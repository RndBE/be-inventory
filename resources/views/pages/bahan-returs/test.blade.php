<!-- Main modal -->
<div wire:ignore.self id="showbahanretur-modal" tabindex="-1" aria-hidden="{{ $isModalOpen ? 'false' : 'true' }}" class="{{ $isModalOpen ? '' : 'hidden' }} overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 pt-12 w-full max-w-md max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-2 dark:border-gray-600">
                <button type="button" wire:click="closeModal" class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div>
                <div class="flex w-full items-center justify-center">
                    <div class="w-[350px] rounded bg-gray-50 px-6 m-4 shadow-lg">
                        <img src="{{ asset('images/logo_be2.png') }}" alt="chippz" class="mx-auto w-32 py-4" />
                        <div class="flex flex-col justify-center items-center gap-2">
                            <h4 class="font-semibold">PT. Arta Teknologi Comunindo</h4>
                            <p class="text-xs text-center">Perum Pesona Bandara No. C-54, Cupuwatu I Purwomartani, Kec. Kalasan, Kabupaten Sleman, Daerah Istimewa Yogyakarta</p>
                        </div>
                        <div class="flex flex-col gap-3 border-b py-6 text-xs">
                            <p class="flex justify-between">
                                <span class="text-gray-400">Tgl Keluar:</span>
                                <span>{{ $tgl_pengajuan}}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Kode Transaksi:</span>
                                <span>{{ $kode_transaksi }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Status:</span>
                                <span>{{ $status }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Divisi:</span>
                                <span>{{ $divisi }}</span>
                            </p>
                        </div>
                        <div class="flex flex-col gap-3 pb-6 pt-2 text-xs">
                            <table class="w-full text-left">
                                <tbody>
                                    @if (!empty($this->bahanReturDetails))
                                        @foreach($this->bahanReturDetails as $detail)
                                            <tr class="flex">
                                                <td class="flex-1 py-1">
                                                    {{ $detail->dataBahan->nama_bahan }}
                                                    ({{ $detail->qty }})
                                                </td>
                                            </tr>
                                            {{-- @php
                                                $unitPrices = json_decode($detail->details);
                                            @endphp --}}
                                            {{-- @foreach($unitPrices as $priceDetail) --}}
                                                <tr class="flex">
                                                    <td class="min-w-[44px]">{{ $detail->qty }} x</td>
                                                    <td class="flex-1">{{ number_format($detail->unit_price) }}</td>
                                                    <td class="flex-1 pl-3">

                                                    </td>
                                                    <td class="w-full text-right">{{ number_format(($detail->qty) * ($detail->unit_price)) }}</td>
                                                </tr>
                                            {{-- @endforeach --}}
                                        @endforeach
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="min-w-[44px]"><strong>Estimasi Harga: </strong></td>
                                            <td class="min-w-[44px]">Rp. {{ number_format($this->bahanReturDetails->sum('sub_total')) }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="3" class="text-center py-2">Tidak ada detail bahan keluar yang ditemukan.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="border-b border border-dashed"></div>
                            <div class="py-4 justify-center items-center flex flex-col gap-2">
                                <p class="flex gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21.3 12.23h-3.48c-.98 0-1.85.54-2.29 1.42l-.84 1.66c-.2.4-.6.65-1.04.65h-3.28c-.31 0-.75-.07-1.04-.65l-.84-1.65a2.567 2.567 0 0 0-2.29-1.42H2.7c-.39 0-.7.31-.7.7v3.26C2 19.83 4.18 22 7.82 22h8.38c3.43 0 5.54-1.88 5.8-5.22v-3.85c0-.38-.31-.7-.7-.7ZM12.75 2c0-.41-.34-.75-.75-.75s-.75.34-.75.75v2h1.5V2Z" fill="#000"></path><path d="M22 9.81v1.04a2.06 2.06 0 0 0-.7-.12h-3.48c-1.55 0-2.94.86-3.63 2.24l-.75 1.48h-2.86l-.75-1.47a4.026 4.026 0 0 0-3.63-2.25H2.7c-.24 0-.48.04-.7.12V9.81C2 6.17 4.17 4 7.81 4h3.44v3.19l-.72-.72a.754.754 0 0 0-1.06 0c-.29.29-.29.77 0 1.06l2 2c.01.01.02.01.02.02a.753.753 0 0 0 .51.2c.1 0 .19-.02.28-.06.09-.03.18-.09.25-.16l2-2c.29-.29.29-.77 0-1.06a.754.754 0 0 0-1.06 0l-.72.72V4h3.44C19.83 4 22 6.17 22 9.81Z" fill="#000"></path></svg> info@bejogja.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
