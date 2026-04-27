<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Aset - {{ $rekap_aset->nomor_aset }}</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #e5e7eb;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 30px 20px;
        }

        .controls {
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-print { background: #4f46e5; color: white; }
        .btn-print:hover { background: #4338ca; }
        .btn-back  { background: #dc2626; color: white; }
        .btn-back:hover  { background: #b91c1c; }

        .label-wrapper {
            background: white;
            border-radius: 10px;
            box-shadow: 0 6px 32px rgba(0,0,0,0.15);
            padding: 20px;
        }

        /*
         * GRID LAYOUT (mirip sketsa):
         *
         *  col:   [  kiri  ]  [     kanan      ]
         *  row1:  Logo         PT Arta Tek...
         *  row2:  Logo         Nomor Aset
         *  row3:  Divisi       QR Code
         *  row4:  Nama Aset    QR Code
         */
        .label {
            width: 420px;
            height: 210px;
            border: 3px solid #111;
            display: grid;
            grid-template-columns: 110px auto 100px;
            grid-template-rows: 55px 45px 50px 1fr;
            overflow: hidden;
            background: white;
        }

        /* ─── Logo (col 1, row 1-2) — PERSEGI 100×100 ─── */
        .cell-logo {
            grid-column: 1;
            grid-row: 1 / 3;
            border-right: 2.5px solid #111;
            border-bottom: 2.5px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
        }
        .cell-logo img {
            width: 95px;
            height: 95px;
            object-fit: contain;
        }

        /* ─── PT Name (col 2-3, row 1) ─── */
        .cell-pt {
            grid-column: 2 / 4;
            grid-row: 1;
            border-bottom: 2px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
        }
        .cell-pt span {
            font-size: 13px;
            font-weight: 800;
            color: #111;
            text-align: center;
            letter-spacing: 0.2px;
            line-height: 1.3;
        }

        /* ─── Nomor Aset (col 2-3, row 2) ─── */
        .cell-nomor {
            grid-column: 2 / 4;
            grid-row: 2;
            border-bottom: 2.5px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
        }
        .cell-nomor span {
            font-size: 12px;
            font-weight: 700;
            color: #111;
            word-break: break-all;
            letter-spacing: 0.3px;
            text-align: center;
        }

        /* ─── Divisi (col 1-2, row 3) ─── */
        .cell-divisi {
            grid-column: 1 / 3;
            grid-row: 3;
            border-right: 2.5px solid #111;
            border-bottom: 2px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
        }
        .cell-divisi span {
            font-size: 14px;
            font-weight: 800;
            color: #111;
            line-height: 1.2;
            word-break: break-word;
            text-align: center;
        }

        /* ─── Nama Aset (col 1-2, row 4) ─── */
        .cell-nama {
            grid-column: 1 / 3;
            grid-row: 4;
            border-right: 2.5px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
        }
        .cell-nama span {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
            word-break: break-word;
            text-align: center;
        }

        /* ─── QR Code (col 3, row 3-4) — PERSEGI 150×150 ─── */
        .cell-qr {
            grid-column: 3;
            grid-row: 3 / 5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
        }
        .cell-qr #qrcode canvas,
        .cell-qr #qrcode img {
            width: 80px !important;
            height: 80px !important;
        }

        /* === PRINT === */
        @media print {
            @page { margin: 15mm; }
            body {
                background: white;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .controls { display: none !important; }
            .label-wrapper { box-shadow: none; padding: 0; border-radius: 0; }
            .label { border: 2.5px solid #000; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="controls">
    <button class="btn btn-back" onclick="window.close()">✕ Tutup Tab</button>
    <button class="btn btn-print" onclick="window.print()">🖨️ Cetak Label</button>
</div>

<div class="label-wrapper">
    <div class="label">

        {{-- Logo (kiri, baris 1-2) --}}
        <div class="cell-logo">
            <img src="{{ asset('images/logo-atc.png') }}" alt="Logo BE"
                 onerror="this.outerHTML='<span style=\'font-size:11px;color:#999\'>Logo</span>'">
        </div>

        {{-- PT Name (kanan, baris 1) --}}
        <div class="cell-pt">
            <span>PT Arta Teknologi Comunindo</span>
        </div>

        {{-- Nomor Aset (kanan, baris 2) --}}
        <div class="cell-nomor">
            <span>{{ $rekap_aset->nomor_aset }}</span>
        </div>

        {{-- Divisi (kiri, baris 3) --}}
        <div class="cell-divisi">
            <span>{{ $rekap_aset->dataUser->dataJobPosition->nama ?? '-' }}</span>
        </div>

        {{-- Nama Barang Aset (kiri, baris 4) --}}
        <div class="cell-nama">
            <span>{{ $rekap_aset->barangAset->nama_barang ?? '-' }}</span>
        </div>

        {{-- QR Code (kanan, baris 3-4) --}}
        <div class="cell-qr">
            <div id="qrcode"></div>
        </div>

    </div>
</div>

<script>
    new QRCode(document.getElementById("qrcode"), {
        text: "{{ route('rekap-aset.scan', $rekap_aset->id) }}",
        width: 80,
        height: 80,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
</script>
</body>
</html>
