<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aset - {{ $rekap_aset->nomor_aset }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 16px;
        }

        /* Header */
        .header {
            width: 100%;
            max-width: 420px;
            background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%);
            border-radius: 16px 16px 0 0;
            padding: 20px 20px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
        }
        .header img {
            height: 44px;
            width: auto;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .header-text h1 {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .header-text p {
            font-size: 11px;
            opacity: 0.75;
            margin-top: 2px;
        }

        /* Card */
        .card {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 0 0 16px 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
        }

        /* Gambar Aset */
        .asset-image {
            width: 100%;
            height: 460px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .asset-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .asset-image .no-image {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
        }
        .asset-image .no-image svg {
            width: 48px;
            height: 48px;
        }
        .asset-image .no-image span {
            font-size: 12px;
        }

        /* Info rows */
        .info-section {
            padding: 4px 0;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child { border-bottom: none; }

        .info-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .icon-blue   { background: #eff6ff; color: #2563eb; }
        .icon-green  { background: #f0fdf4; color: #16a34a; }
        .icon-purple { background: #faf5ff; color: #7c3aed; }
        .icon-orange { background: #fff7ed; color: #ea580c; }
        .icon-teal   { background: #f0fdfa; color: #0d9488; }

        .info-icon svg { width: 18px; height: 18px; }

        .info-content {
            flex: 1;
        }
        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.4;
            word-break: break-word;
        }

        /* Badge kondisi */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-baik   { background: #dcfce7; color: #15803d; }
        .badge-rusak  { background: #fee2e2; color: #dc2626; }
        .badge-default { background: #f1f5f9; color: #64748b; }

        /* Footer */
        .footer {
            width: 100%;
            max-width: 420px;
            text-align: center;
            margin-top: 16px;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <img src="{{ asset('images/logo-atc.png') }}" alt="Logo ATC"
         onerror="this.style.display='none'">
    <div class="header-text">
        <h1>PT Arta Teknologi Comunindo</h1>
        <p>Informasi Aset Perusahaan</p>
    </div>
</div>

<div class="card">

    {{-- Gambar Aset --}}
    <div class="asset-image">
        @php
            $fileId = null;
            if ($rekap_aset->link_gambar && strpos($rekap_aset->link_gambar, '/d/') !== false) {
                $fileId = explode('/d/', $rekap_aset->link_gambar)[1];
                $fileId = explode('/', $fileId)[0];
            }
        @endphp
        @if($fileId)
            <img src="https://drive.google.com/thumbnail?id={{ $fileId }}&sz=w400"
                 alt="Foto Aset"
                 onerror="this.parentElement.innerHTML='<div class=\'no-image\'><svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg><span>Gambar tidak tersedia</span></div>'">
        @else
            <div class="no-image">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Tidak ada gambar</span>
            </div>
        @endif
    </div>

    <div class="info-section">

        {{-- ID Aset --}}
        <div class="info-row">
            <div class="info-icon icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                </svg>
            </div>
            <div class="info-content">
                <div class="info-label">ID Aset</div>
                <div class="info-value">{{ $rekap_aset->nomor_aset }}</div>
            </div>
        </div>

        {{-- Nama Aset / Merk / Serial Number --}}
        <div class="info-row">
            <div class="info-icon icon-purple">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div class="info-content">
                <div class="info-label">Nama Aset / Merk</div>
                <div class="info-value">
                    {{ $rekap_aset->barangAset->nama_barang ?? '-' }}
                    @if($rekap_aset->serial_number)
                        <span style="color:#64748b; font-weight:500;"> / {{ $rekap_aset->serial_number }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Posisi Aset (Divisi) --}}
        <div class="info-row">
            <div class="info-icon icon-green">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="info-content">
                <div class="info-label">Posisi Aset</div>
                <div class="info-value">{{ $rekap_aset->dataUser->dataJobPosition->nama ?? '-' }}</div>
            </div>
        </div>

        {{-- Tahun Perolehan --}}
        <div class="info-row">
            <div class="info-icon icon-orange">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="info-content">
                <div class="info-label">Tahun Perolehan</div>
                <div class="info-value">
                    {{ $rekap_aset->tgl_perolehan ? \Carbon\Carbon::parse($rekap_aset->tgl_perolehan)->format('Y') : '-' }}
                </div>
            </div>
        </div>

        {{-- Kondisi --}}
        <div class="info-row">
            <div class="info-icon icon-teal">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="info-content">
                <div class="info-label">Kondisi</div>
                <div class="info-value">
                    @if($rekap_aset->kondisi === 'Baik')
                        <span class="badge badge-baik">✓ Baik</span>
                    @elseif($rekap_aset->kondisi === 'Rusak')
                        <span class="badge badge-rusak">✗ Rusak</span>
                    @else
                        <span class="badge badge-default">{{ $rekap_aset->kondisi ?? '-' }}</span>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

<div class="footer">
    Scan QR Code pada label aset untuk informasi ini<br>
    &copy; {{ date('Y') }} PT Arta Teknologi Comunindo
</div>

</body>
</html>
