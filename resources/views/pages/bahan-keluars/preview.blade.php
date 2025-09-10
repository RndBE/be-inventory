@section('title', 'Preview Bahan Keluar | BE INVENTORY')
<x-app-layout>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            color: #000;
        }

        h2 {
            margin: 0;
            padding: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        h3 {
            text-align: center;
            margin: 15px 0;
            font-size: 16px;
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        .no-border td, .no-border th {
            border: none !important;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }

        /* @media print {
            button, a { display: none !important; }
        } */
    </style>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex justify-between items-center w-full">
            <span class="text-xs font-semibold">FORM PENGAJUAN PENGAMBILAN BARANG</span>

            <div class="flex gap-2">
                <a href="{{ route('bahan-keluars.index') }}"
                   class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                   Kembali
                </a>
                <button onclick="printDiv('print-section')"
                    class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                    Print / Preview PDF
                </button>
            </div>
        </div>
    </x-app.secondary-header>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="pos intro-y grid grid-cols-12 gap-5 mt-5">
            <div class="intro-y col-span-12">
                <div id="print-section" class="bg-white p-5">
                    <!-- Header -->
                    <table style=" width: 100%;border-collapse: collapse;">
                        <tr>
                            <th style="text-align: left;vertical-align: top;width: 30%;">
                                <img style="display: block;max-width: 100%;height: auto;" src="{{ asset('images/Picture.png') }}" alt="Logo">
                            </th>
                            <td style="border-bottom: 2px solid black;vertical-align: top;padding: 5px;text-align: left;">
                                <h2 style="font-size: 18px;">PT. ARTA TEKNOLOGI COMUNINDO</h2>
                                <p style="margin: 5px 0;line-height: 1.5;font-size: 12px;">Kadirojo I, Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta <br> Ph./Fax. (0274) 4986899 Website: <a href="https://www.be-jogja.com" target="_blank">https://www.be-jogja.com</a>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <!-- Centered H3 -->
                    <h3 class="pt-6">FORM PENGAJUAN PENGAMBILAN BARANG PT. ARTA TEKNOLOGI COMUNINDO</h3>

                    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
                        <tr style="text-align: left;vertical-align: top;">
                            <td style="border: 1px solid black;width: 30%;"><strong>Kode Transaksi</strong></td>
                            <td style="border: 1px solid black">: {{ $bahanKeluar->kode_transaksi }}</td>
                        </tr>
                        <tr style="text-align: left;vertical-align: top;">
                            <td style="border: 1px solid black;width: 30%;"><strong>Divisi</strong></td>
                            <td style="border: 1px solid black">: {{ $bahanKeluar->divisi }}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black;"><strong>Project</strong></td>
                            <td style="border: 1px solid black">: {{ $bahanKeluar->tujuan }}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black;"><strong>Keterangan</strong></td>
                            <td style="border: 1px solid black">: {{ $bahanKeluar->keterangan }}</td>
                        </tr>
                    </table>

                    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid black; width: 5%;">No</th>
                            <th style="border: 1px solid black; width: 10%;">Kode Barang</th>
                            <th style="border: 1px solid black; width: 25%;">Nama</th> <!-- Kolom lebih lebar -->
                            <th style="border: 1px solid black; width: 25%;">Penempatan</th> <!-- Kolom lebih lebar -->
                            <th style="border: 1px solid black; width: 5%;">Qty</th>
                            <th style="border: 1px solid black; width: 10%;">Satuan</th>
                            <th style="border: 1px solid black; width: 10%;">Ceklis Purchasing</th>
                            <th style="border: 1px solid black; width: 10%;">Ceklis Pengambil</th>
                            <th style="border: 1px solid black; width: 25%;">Kondisi Barang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalSubTotal = 0;
                            @endphp
                            @foreach ($bahanKeluar->bahanKeluarDetails as $index => $detail)
                                @php
                                    $details = json_decode($detail->details, true); // Decode JSON details column
                                @endphp
                                <tr>
                                    <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                                    <td style="border: 1px solid black">{{ $detail->dataBahan->kode_bahan ?? null }}</td>
                                    <td style="border: 1px solid black">
                                        @if ($detail->dataBahan)
                                            {{ $detail->dataBahan->nama_bahan }}
                                        @elseif ($detail->dataProduk)
                                            {{ $detail->dataProduk->nama_bahan }}
                                            ({{ $detail->serial_number ?? 'N/A' }})
                                        @elseif ($detail->dataProdukJadi)
                                            {{ $detail->dataProdukJadi->nama_produk }}
                                            ({{ $detail->serial_number ?? 'N/A' }})
                                        @else
                                            Data tidak tersedia
                                        @endif
                                    </td>
                                    <td style="border: 1px solid black;text-align: center;">{{ $detail->dataBahan->penempatan ?? null }}</td>
                                    <td style="border: 1px solid black;text-align: center;">{{ $detail->qty }}</td>
                                    <td style="border: 1px solid black;text-align: center;">{{ $detail->dataBahan->dataUnit->nama ?? 'Pcs' }}</td>
                                    <td style="border: 1px solid black"></td>
                                    <td style="border: 1px solid black"></td>
                                    <td style="border: 1px solid black"></td>
                                </tr>
                                @php
                                    $totalSubTotal += $detail->qty;
                                @endphp
                            @endforeach
                            <tr>
                                <td style="border: 1px solid black; text-align: left;"></td>
                                <td style="border: 1px solid black; text-align: left;"></td>
                                <td colspan="2" style="border: 1px solid black; text-align: right; font-weight: bold;"> Total Pengeluaran Barang </td>
                                <td style="border: 1px solid black; text-align: center; border-right: none;">
                                    {{ $totalSubTotal }}
                                </td>
                                <td colspan="4" style="border: 1px solid black; text-align: right; font-weight: bold;"> </td>
                            </tr>
                        </tbody>

                    </table>

                    <table style="width: 100%;border-collapse: collapse;padding-top:10;">
                        <tr style="text-align: left; vertical-align: top;">
                            <td style=" text-align: center;"><strong>Pengambil</strong></td>
                            <td colspan="2" style=" text-align: center;"><strong>Purchasing</strong></td>
                            <td style=" text-align: center;"><strong>Leader</strong></td>
                        </tr>
                        <tr>
                            <td style="text-align: center; width: 25%;">
                                <div style="height: 80px; width: 150px;"></div>
                            </td>
                            <td colspan="2" style="text-align: center; width: 25%;">
                                <div style="height: 80px; width: 150px;"></div>
                            </td>
                            <td style="text-align: center; width: 25%;">
                                <div style="height: 80px; width: 150px;"></div>
                            </td>
                        </tr>

                        <tr style="text-align: left; vertical-align: top;">
                            <td style="text-align: center;">
                                {{ $bahanKeluar->dataUser->name ?? null }}
                            </td>
                            <td colspan="2" style="text-align: center;">{{ $purchasingUser->name }}</td>
                            <td style="text-align: center;">{{ $leaderName ?? '' }}</td>
                        </tr>


                        <tr>
                            <td colspan="4"  style="text-align: center;padding:7;"><strong> Mengetahui, </strong></td>
                        </tr>
                        <tr style="text-align: left; vertical-align: top;">
                            <td style="text-align: center;">
                                {{-- @if($hasProduk)
                                    <strong>Produksi</strong>
                                @endif --}}
                            </td>
                            <td colspan="2" style=" text-align: center;"><strong>Manager Admin</strong></td>
                            <td style=" text-align: center;"><strong></strong></td>
                        </tr>
                        <tr>
                            <td style="text-align: center; width: 33%;">
                                <div style="height: 80px; width: 150px;"></div>
                            </td>
                            <td colspan="2" style="text-align: center; width: 33%;">
                                <div style="height: 80px; width: 150px;"></div>
                            </td>
                            <td style="text-align: center; width: 33%;">
                                <div style="height: 80px; width: 150px;"></div>
                            </td>
                        </tr>
                        <tr style="text-align: left; vertical-align: top;">
                            <td style="text-align: center;">
                                {{-- @if($hasProduk)
                                    RHOMADONI
                                @endif --}}
                            </td>
                            <td colspan="2" style="text-align: center;">{{ $adminManagerceUser->name ?? '' }}</td>
                            <td style="text-align: center;"></td>
                        </tr>
                        <tr>
                            <td colspan="4"  style="text-align: center;padding:7;"><strong>  </strong></td>
                        </tr>
                        <tr style="text-align: left; vertical-align: top;">
                            <td style="text-align: center;"></td>
                            <td style="text-align: center;"></td>
                            <td colspan="2" style="text-align: center;">Tgl Pengambilan: </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printDiv(divId) {
            const printContents = document.getElementById(divId).innerHTML;
            const printWindow = window.open('', '_blank', 'width=1100,height=600');
            const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
                .map(node => node.outerHTML).join('');

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print Preview</title>
                        ${styles}
                        <style>@media print { button { display: none !important; } }</style>
                    </head>
                    <body>${printContents}</body>
                </html>
            `);

            printWindow.document.close();
            printWindow.onload = () => {
                printWindow.focus();
                printWindow.print();
            };
        }
    </script>
</x-app-layout>
