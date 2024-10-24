@section('title', 'Detail Transaksi Bahan Masuk | BE INVENTORY')
<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <article class="overflow-hidden">
            <div class="bg-white rounded-b-md">
                <div class="p-9">
                    <div class="space-y-6 text-slate-700">
                        <img class="object-cover h-12" src="{{ asset('images/logo_be2.png') }}" />
                    </div>
                </div>
                <div class="p-9">
                    <div class="flex w-full">
                        <div class="grid grid-cols-4 gap-12">
                            <div class="text-sm font-light text-slate-500">
                                <p class="text-sm font-normal text-slate-700">Detail Transaksi:</p>
                            </div>
                            <div class="text-sm font-light text-slate-500">
                                <p class="text-sm font-normal text-slate-700">Divisi:</p>
                                <p class="text-sm font-normal text-slate-700">Purchasing</p>
                            </div>
                            <div class="text-sm font-light text-slate-500">
                                <p class="text-sm font-normal text-slate-700">Kode Transaksi:</p>
                                <p>{{ $kode_transaksi }}</p>
                                <p class="mt-2 text-sm font-normal text-slate-700">Tanggal Transaksi</p>
                                <p>{{ \Carbon\Carbon::parse($tgl_masuk)->format('d F Y H:i:s') }}</p> <!-- Format tanggal -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-9">
                    <div class="flex flex-col mx-0 mt-8">
                        <table class="min-w-full divide-y divide-slate-500">
                            <thead>
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-normal text-slate-700 sm:pl-6 md:pl-0">Gambar</th>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-normal text-slate-700 sm:pl-6 md:pl-0">Nama Bahan</th>
                                    <th scope="col" class="hidden py-3.5 px-3 text-right text-sm font-normal text-slate-700 sm:table-cell">Quantity</th>
                                    <th scope="col" class="hidden py-3.5 px-3 text-right text-sm font-normal text-slate-700 sm:table-cell">Unit Price</th>
                                    <th scope="col" class="py-3.5 pl-3 pr-4 text-right text-sm font-normal text-slate-700 sm:pr-6 md:pr-0">Sub Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bahanSetengahjadiDetails as $detail)
                                <tr class="border-b border-slate-200">
                                    <td class="py-4 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                                        <img src="{{ $detail->dataProdukProduksi->gambar ? asset('storage/' . $detail->dataProdukProduksi->gambar) : asset('images/image-4@2x.jpg') }}" alt="Gambar {{ $detail->dataProdukProduksi->nama_produk }}" class="h-auto w-24 rounded-lg">
                                    </td>
                                    <td class="py-4 pl-4 pr-3 text-sm sm:pl-6 md:pl-0">
                                        <div class="font-medium text-slate-700">{{ $detail->dataProdukProduksi->nama_produk }}</div>
                                    </td>
                                    <td class="hidden px-3 py-4 text-sm text-right text-slate-500 sm:table-cell"><span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">{{ $detail->qty }}</span></td>
                                    <td class="hidden px-3 py-4 text-sm text-right text-slate-500 sm:table-cell">Rp. {{ number_format($detail->unit_price, 2) }}</td>
                                    <td class="py-4 pl-3 pr-4 text-sm text-right text-slate-500 sm:pr-6 md:pr-0">Rp. {{ number_format($detail->sub_total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th scope="row" colspan="4" class="hidden pt-6 pl-6 pr-3 text-sm font-light text-right text-slate-500 sm:table-cell md:pl-0">Total Harga</th>
                                    <th scope="row" class="pt-6 pl-4 pr-3 text-sm font-light text-left text-slate-500 sm:hidden">Total Harga</th>
                                    <td class="pt-6 pl-3 pr-4 text-sm text-right text-slate-500 sm:pr-6 md:pr-0">Rp. {{ number_format($bahanSetengahjadiDetails->sum('sub_total'), 2) }}</td>
                                </tr>
                                <!-- Tambahkan baris untuk Discount, Tax, dan Total jika perlu -->
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mt-9 p-9">
                    <div class="border-t pt-9 border-slate-200">
                        <div class="mt-2 flex items-center justify-end gap-x-6">
                            <a href="{{ route('bahan-setengahjadis.index') }}" type="button" class="text-sm font-semibold leading-6 text-gray-900">Kembali</a>
                            <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Print</button>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </div>
</x-app-layout>
