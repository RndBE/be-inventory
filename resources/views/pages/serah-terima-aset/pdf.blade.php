{{--
    Berita Acara Serah Terima Aset — mengikuti format resmi PT. Arta Teknologi
    Comunindo (berkas Word "BERITA ACARA SERAH TERIMA ASET").

    Kerangkanya dua pihak: PIHAK PERTAMA karyawan yang menyerahkan, PIHAK KEDUA
    HRD yang menerima, ditambah satu blok "Mengetahui" untuk Leader GA.

    Kotak tanda tangan sengaja dibiarkan kosong: dokumen ini dicetak lalu
    ditandatangani basah saat serah terima berlangsung. Mencetak tanda tangan
    digital tanpa ada persetujuan di sistem sama saja menerbitkan surat atas
    nama orang yang tidak pernah menyetujuinya.

    Bagian tanggal yang di berkas Word dikosongkan untuk ditulis tangan di sini
    diisi otomatis dari data, jadi tidak ada kolom yang terlupa.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>BAST {{ $bast->kode_bast }}</title>
    <style>
        /* Margin atas dilebihkan untuk menampung kop yang jadi header berjalan.
           Isi dokumen mulai di bawahnya, jadi tidak pernah saling menimpa. */
        @page { margin: 33mm 18mm 20mm; }
        /* line-height dirapatkan sengaja: satu berita acara harus muat dalam satu
           lembar isi + satu lembar lampiran seperti formulir aslinya. Kalau meluber,
           blok tanda tangan terpisah sendirian di lembar kedua dan dokumennya
           terbaca seperti salah cetak. */
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; line-height: 1.4; }

        /* Kop mengikuti dokumen PDF lain di sistem ini supaya seragam.
           position: fixed membuatnya tercetak di SETIAP halaman dan keluar dari
           alur dokumen — halaman lampiran pun tetap berkop. */
        table.kop {
            position: fixed;
            top: -26mm; left: 0; right: 0;
            width: 100%; border-collapse: collapse;
        }
        table.kop td { vertical-align: top; padding: 0 0 6px; }
        table.kop .logo { width: 30%; }
        table.kop .logo img { display: block; max-width: 190px; height: auto; }
        table.kop .identitas { border-bottom: 2px solid #111; }
        table.kop .identitas h2 { font-size: 14px; margin: 0 0 2px; letter-spacing: 1px; }
        table.kop .identitas p { font-size: 8.5px; margin: 0; line-height: 1.4; }

        h1 { font-size: 12px; text-align: center; text-transform: uppercase; margin: 0 0 2px; text-decoration: underline; }
        .nomor { text-align: center; font-size: 11px; margin-bottom: 14px; font-weight: bold; text-decoration: underline; }

        p { margin: 0 0 6px; text-align: justify; }
        .indent { text-indent: 32px; }

        /* Identitas pihak. Label lebar tetap supaya titik dua sejajar seperti
           di berkas aslinya. */
        table.pihak { width: 100%; border-collapse: collapse; margin: 0 0 8px 18px; }
        table.pihak td { padding: 0; vertical-align: top; font-size: 10.5px; }
        table.pihak td.urut { width: 18px; }
        table.pihak td.label { width: 120px; }
        table.pihak td.pemisah { width: 12px; }

        table.aset { width: 100%; border-collapse: collapse; margin: 6px 0 10px; }
        table.aset th, table.aset td { border: 1px solid #333; padding: 4px 5px; }
        table.aset th { background: #eee; font-size: 9px; text-align: center; vertical-align: middle; }
        table.aset td { font-size: 9.5px; }
        table.aset td.tengah { text-align: center; }
        .nihil { border: 1px solid #333; padding: 12px; text-align: center; font-style: italic; background: #fafafa; margin-bottom: 10px; }

        /* Blok tanda tangan memakai border tabel seperti berkas aslinya.
           Ruang tanda tangannya dari padding, bukan tinggi sel: dompdf keliru
           merender sel bertinggi tetap yang terbelah antar halaman. */
        /* table-layout: fixed supaya kedua kolom persis 50% — tanpa ini lebar sel
           mengikuti panjang nama, jadi garis pemisah PIHAK PERTAMA/KEDUA meleset
           dari tengah dan blok "Mengetahui" di bawahnya ikut terlihat miring. */
        table.ttd { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: avoid; table-layout: fixed; }
        table.ttd td.kolom { width: 50%; }
        table.ttd td { border: 1px solid #333; text-align: center; font-size: 10px; padding: 3px 6px; word-wrap: break-word; }
        table.ttd td.peran { font-weight: bold; }
        /* ~13mm, cukup untuk tanda tangan basah sekaligus cap kalau diperlukan. */
        table.ttd td.ruang { padding-bottom: 48px; }
        table.ttd td.nama { font-weight: bold; }

        .catatan { margin-top: 10px; font-size: 9.5px; }
        .catatan ol { margin: 2px 0 0 16px; padding: 0; }

        /* Halaman lampiran. page-break-before memaksanya mulai di lembar baru. */
        .lampiran { page-break-before: always; }
        .lampiran h3 { font-size: 11px; font-weight: bold; margin: 0; }

        /* Nomor halaman TIDAK digambar dari CSS.
           counter(pages) tidak terselesaikan di dompdf 3.0 — hasilnya "Hal 2 dari 0" —
           dan elemen position:fixed yang dideklarasikan setelah page-break tidak
           tergambar di halaman sebelumnya, sehingga halaman 1 kehilangan footernya.
           Footernya digambar langsung ke kanvas lewat page_text() di controller,
           yang memang menyediakan {PAGE_NUM} dan {PAGE_COUNT}. */
    </style>
</head>
<body>

<table class="kop">
    <tr>
        <td class="logo">
            <img src="{{ public_path('images/Picture.png') }}" alt="Logo">
        </td>
        <td class="identitas">
            <h2>PT. ARTA TEKNOLOGI COMUNINDO</h2>
            <p>
                Kadirojo I, Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta<br>
                Ph./Fax. (0274) 4986899 &nbsp;&middot;&nbsp; Website: https://www.be-jogja.com
            </p>
        </td>
    </tr>
</table>

<h1>Berita Acara Serah Terima Aset</h1>
<div class="nomor">Nomor: {{ $bast->kode_bast }}</div>

{{-- Tanggal pembuatan berita acara. Kalau datanya kosong, kata-katanya
     dikosongkan seperti formulir aslinya agar bisa ditulis tangan. --}}
<p class="indent">
    Pada hari {{ $tglDibuat['hari'] ?? '......' }}
    tanggal {{ $tglDibuat['tanggal'] ?? '......' }}
    bulan {{ $tglDibuat['bulan'] ?? '.......' }}
    tahun {{ $tglDibuat['tahun'] ?? '...............' }}
    ({{ $tglDibuat['angka'] ?? '../../......' }})
    bertempat di PT. Arta Teknologi Comunindo, kami yang bertanda tangan di bawah ini:
</p>

<table class="pihak">
    <tr>
        <td class="urut">1.</td>
        <td class="label">Nama</td>
        <td class="pemisah">:</td>
        <td><strong>{{ $bast->dataKaryawan->name ?? '-' }}</strong></td>
    </tr>
    <tr>
        <td></td>
        <td class="label">Jabatan Terdahulu</td>
        <td class="pemisah">:</td>
        <td><em>{{ $bast->jabatan_terdahulu ?: '-' }}</em></td>
    </tr>
    <tr>
        <td></td>
        <td class="label">Divisi Terdahulu</td>
        <td class="pemisah">:</td>
        <td><em>{{ $bast->divisi_terdahulu ?: '-' }}</em></td>
    </tr>
    <tr>
        <td></td>
        <td colspan="3">Sebagai yang menyerahkan aset dan selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong></td>
    </tr>
</table>

<table class="pihak">
    {{-- Semuanya dari kolom snapshot pada dokumen ini, bukan dari relasi user.
         Koreksi data master di kemudian hari tidak boleh mengubah dokumen yang
         sudah ditandatangani. --}}
    <tr>
        <td class="urut">2.</td>
        <td class="label">Nama</td>
        <td class="pemisah">:</td>
        <td><strong>{{ $pihakKedua['nama'] ?: '-' }}</strong></td>
    </tr>
    <tr>
        <td></td>
        <td class="label">Nomor ID</td>
        <td class="pemisah">:</td>
        <td>{{ $pihakKedua['nomor_id'] ?: '-' }}</td>
    </tr>
    <tr>
        <td></td>
        <td class="label">Jabatan</td>
        <td class="pemisah">:</td>
        <td><em>{{ $pihakKedua['jabatan'] ?: '-' }}</em></td>
    </tr>
    <tr>
        <td></td>
        <td class="label">Divisi</td>
        <td class="pemisah">:</td>
        <td><em>{{ $pihakKedua['divisi'] ?: '-' }}</em></td>
    </tr>
    <tr>
        <td></td>
        <td colspan="3">selanjutnya disebut sebagai <strong>PIHAK KEDUA.</strong></td>
    </tr>
</table>

<p class="indent">
    Dengan ini, <strong>PIHAK PERTAMA</strong> telah menyerahkan dan <strong>PIHAK KEDUA</strong>
    telah menerima aset milik perusahaan dengan spesifikasi sebagai berikut:
</p>

@if($bast->bebas_aset)
    {{-- Keadaan yang sah dan cukup sering: karyawan sudah mengembalikan semua
         asetnya sebelum BAST dibuat. Dinyatakan tegas, bukan tabel kosong, supaya
         dokumennya tetap bisa dipakai HR sebagai keterangan bebas aset. --}}
    <div class="nihil">
        Berdasarkan pencatatan sistem inventory, <strong>PIHAK PERTAMA</strong> tidak lagi memegang
        aset perusahaan pada saat berakhirnya masa kerja.<br>
        Dokumen ini berlaku sebagai <strong>Surat Keterangan Bebas Aset</strong>.
    </div>
@else
    <table class="aset">
        <thead>
            <tr>
                <th style="width:24px;">No</th>
                <th>Nama Aset/Barang</th>
                <th style="width:80px;">Merek/Tipe</th>
                <th style="width:72px;">Tempat</th>
                <th style="width:82px;">Nomor Seri/IMEI</th>
                <th style="width:40px;">Jumlah</th>
                <th style="width:44px;">Kondisi</th>
                <th style="width:40px;">Checklist</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bast->aset_diserahkan->values() as $i => $detail)
                <tr>
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td>{{ $detail->dataAset->barangAset->nama_barang ?? '-' }}</td>
                    <td>{{ $detail->dataAset->merek ?: '-' }}</td>
                    {{-- tempat_serah dibekukan saat BAST dibuat. Kalau dibaca dari
                         relasi ruangan, cetak ulang setelah BAST selesai akan kosong
                         karena ruangan aset sudah dilepas. --}}
                    <td>{{ $detail->tempat_serah ?: '-' }}</td>
                    <td>{{ $detail->dataAset->serial_number ?: '-' }}</td>
                    <td class="tengah">{{ $detail->dataAset->jumlah_aset ?? 1 }}</td>
                    <td class="tengah">{{ $detail->kondisi_serah }}</td>
                    {{-- Sengaja kosong: dicentang tangan saat barangnya diperiksa. --}}
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<p class="indent">
    Aset ini diserahkan karena berakhirnya penggunaan oleh <strong>PIHAK PERTAMA</strong> kepada
    <strong>PIHAK KEDUA</strong> terhitung pada hari {{ $tglSerah['hari'] ?? '......' }}
    tanggal {{ $tglSerah['tanggal'] ?? '......' }}
    bulan {{ $tglSerah['bulan'] ?? '.......' }}
    tahun {{ $tglSerah['tahun'] ?? '...............' }}
    ({{ $tglSerah['angka'] ?? '../../......' }}).
</p>

<p class="indent">
    Setelah serah terima ini dilakukan, maka tanggung jawab atas aset tersebut sepenuhnya berada
    pada <strong>PIHAK KEDUA</strong>. <strong>PIHAK PERTAMA</strong> tidak memiliki tanggung jawab
    lebih lanjut atas barang yang telah diserahkan tersebut.
</p>

<p class="indent">
    Demikian surat serah terima ini dibuat dengan sebenar-benarnya, untuk digunakan sebagaimana
    mestinya.
</p>

<table class="ttd">
    {{-- width 50% di baris pertama ini yang menentukan lebar kolom; dompdf
         mengabaikan <colgroup>, dan baris ber-colspan di bawah tidak ikut
         menghitung lebar. --}}
    <tr>
        <td class="peran kolom">PIHAK PERTAMA</td>
        <td class="peran kolom">PIHAK KEDUA</td>
    </tr>
    <tr>
        {{-- padding-bottom pada sel inilah yang menyediakan ruang tanda tangan basah. --}}
        <td class="ruang"></td>
        <td class="ruang"></td>
    </tr>
    <tr>
        <td class="nama">{{ $bast->dataKaryawan->name ?? '...........................' }}</td>
        <td class="nama">{{ $pihakKedua['nama'] ?: '...........................' }}</td>
    </tr>
    {{-- "Mengetahui" dan "Leader GA" digabung dalam satu sel dua baris, bukan dua
         baris tabel: menghemat tinggi supaya seluruh isi tetap muat satu lembar. --}}
    <tr>
        <td class="peran" colspan="2">Mengetahui<br>Leader GA</td>
    </tr>
    <tr>
        <td class="ruang" colspan="2"></td>
    </tr>
    <tr>
        <td class="nama" colspan="2">{{ $namaGa !== '' ? $namaGa : '...........................' }}</td>
    </tr>
</table>

<div class="catatan">
    Catatan:
    <ol>
        <li>Lampiran aset</li>
        @if($bast->aset_sudah_kembali->isNotEmpty())
            {{-- Aset yang sudah dikembalikan sebelum BAST dibuat. Tidak masuk tabel
                 utama karena bukan yang diserahkan hari ini, tapi tetap dicantumkan
                 supaya dokumennya jadi rekening lengkap — tanpa ini akan timbul
                 pertanyaan soal aset yang pernah tercatat atas nama karyawan
                 tapi tidak muncul di mana pun. --}}
            <li>
                Aset yang telah dikembalikan sebelum berita acara ini dibuat:
                @foreach($bast->aset_sudah_kembali->values() as $detail)
                    {{ $detail->dataAset->barangAset->nama_barang ?? '-' }}
                    ({{ $detail->dataAset->nomor_aset ?? '-' }}
                    @if($detail->detailPeminjaman?->tgl_kembali)
                        , {{ \Carbon\Carbon::parse($detail->detailPeminjaman->tgl_kembali)->format('d/m/Y') }}
                    @endif
                    ){{ $loop->last ? '' : ';' }}
                @endforeach
            </li>
        @endif
        @if($bast->keterangan)
            <li>{{ $bast->keterangan }}</li>
        @endif
    </ol>
</div>

{{-- Halaman lampiran dokumentasi. Sengaja dibiarkan kosong: BAST di sistem ini
     belum menyimpan foto, jadi lembarnya dicetak untuk ditempeli dokumentasi
     secara fisik — sama seperti pada berkas Word aslinya. --}}
<div class="lampiran">
    <h3>LAMPIRAN DOKUMENTASI</h3>
</div>

</body>
</html>
