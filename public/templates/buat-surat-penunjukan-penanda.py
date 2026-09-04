# -*- coding: utf-8 -*-
"""
Bangun template placeholder dari SURAT PENUNJUKAN PERUBAHAN DATA.docx.

Word memecah kalimat jadi banyak <w:r> mengikuti riwayat suntingan, jadi
pencarian teks biasa gagal. Skrip ini mengganti teks LINTAS run: menghitung
posisi karakter di seluruh <w:t> satu paragraf, lalu menaruh penggantinya di
<w:t> pertama yang tersentuh dan menghapus sisa karakternya dari <w:t> lain.

Hasilnya: seluruh format asli (font, tab stop, spasi, tabel, kop, penomoran)
utuh, hanya teksnya yang jadi ${placeholder}.
"""
import zipfile, re, io, shutil, os

SRC = 'd:/BE Software/BE PROJECT/be-inventory/public/templates/SURAT PENUNJUKAN PERUBAHAN DATA.docx'
DST = 'd:/BE Software/BE PROJECT/be-inventory/public/templates/surat-penunjukan-penanda.docx'

T_RE = re.compile(r'(<w:t(?:\s[^>]*)?>)(.*?)(</w:t>)', re.S)
P_RE = re.compile(r'<w:p(?:\s[^>]*)?>.*?</w:p>', re.S)


def unesc(s):
    return (s.replace('&amp;', '&').replace('&lt;', '<').replace('&gt;', '>')
             .replace('&quot;', '"').replace('&#39;', "'"))


def esc(s):
    return (s.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;'))


def ganti_di_paragraf(par, cari, ganti):
    """Ganti `cari` jadi `ganti` di dalam satu paragraf, lintas <w:t>."""
    potongan = list(T_RE.finditer(par))
    if not potongan:
        return par, False

    penuh = ''.join(unesc(m.group(2)) for m in potongan)
    idx = penuh.find(cari)
    if idx < 0:
        return par, False

    akhir = idx + len(cari)
    hasil, jalan, sudah = [], 0, False
    posisi = 0

    for m in potongan:
        isi = unesc(m.group(2))
        a, b = jalan, jalan + len(isi)
        jalan = b

        hasil.append(par[posisi:m.start()])
        posisi = m.end()

        if b <= idx or a >= akhir:          # di luar rentang
            baru = isi
        else:
            depan = isi[:max(0, idx - a)]
            belakang = isi[max(0, akhir - a):] if akhir > a else ''
            if not sudah:
                baru = depan + ganti + belakang
                sudah = True
            else:
                baru = depan + belakang

        buka = m.group(1)
        if baru != baru.strip() and 'xml:space' not in buka:
            buka = buka[:-1] + ' xml:space="preserve">'
        hasil.append(buka + esc(baru) + m.group(3))

    hasil.append(par[posisi:])
    return ''.join(hasil), True


def ganti(xml, cari, ganti_dengan, semua=False):
    par = P_RE.findall(xml)
    n = 0
    for pp in par:
        baru, kena = ganti_di_paragraf(pp, cari, ganti_dengan)
        if kena:
            xml = xml.replace(pp, baru, 1)
            n += 1
            if not semua:
                break
    return xml, n


xml = zipfile.ZipFile(SRC).read('word/document.xml').decode('utf-8')

TUGAS = [
    # (teks di template, placeholder, semua kemunculan?)
    ('008/ACC-PD/IX/2026', '${nomor_surat}', True),
    ('02 September 2026', '${tanggal_surat}', True),
    ('Nomor 003 tanggal 01 September 2026', 'Nomor ${nomor_pengajuan} tanggal ${tanggal_pengajuan}', False),
    ('Nomor 003 yang telah diajukan', 'Nomor ${nomor_pengajuan} yang telah diajukan', False),
    ('dari Tim Supply Chain mengenai', 'dari ${tim_pemohon} mengenai', False),
    ('oleh Tim Supply Chain dan memperoleh', 'oleh ${tim_pemohon} dan memperoleh', False),
    ('perubahan harga barang dan biaya pengiriman', '${pokok_perubahan}', False),
    ('PBI-20260821-0965', '${daftar_kode}', False),
    # tabel rincian: baris pertama jadi baris contoh, dua sisanya dibuang
    ('$ 230,00', '${data_lama}', False),
    ('$ 302,00', '${data_baru}', False),
    # tanda tangan
    ('Maritza Isyaura Putri Rizma', '${nama_dibuat}', False),
    ('Dewi Pusporini', '${nama_diperiksa_fat}', False),
    ('Wahyu Nurul Haryanto', '${nama_setuju_fat}', False),
    ('Nofiyanto', '${nama_setuju_software}', False),
    ('Shandy Bagus Ferdiansyah', '${nama_pelaksana_ttd}', False),
    # Nomor ID penanda tangan. Ikut jadi penanda supaya nama dan nomornya
    # selalu datang dari sumber yang sama; nomor tetap di dokumen akan
    # tertinggal begitu orangnya berganti.
    ('ID. 004/FATSC/VII/2024', '${id_dibuat}', False),
    ('ID. 003/FATSC/VI/2024', '${id_diperiksa_fat}', False),
    ('ID. 001/FATSC/III/2013', '${id_setuju_fat}', False),
    ('ID. 001/SOFTW/I/2015', '${id_setuju_software}', False),
    ('004/SOFTW/XII/2025', '${id_pelaksana}', False),
    # halaman konfirmasi: isian yang di dokumen aslinya dikosongkan
    ('Tanggal Pelaksanaan :', 'Tanggal Pelaksanaan : ${tanggal_pelaksanaan}', False),
    ('Nama Pelaksana :', 'Nama Pelaksana : ${nama_pelaksana}', False),
]

for cari, isi, semua in TUGAS:
    xml, n = ganti(xml, cari, isi, semua)
    print('%-46s -> %-34s %d paragraf' % (cari[:44], isi, n))

# Fadel muncul dua kali (koordinasi halaman 1, diketahui halaman konfirmasi)
xml, n = ganti(xml, 'Fadel Muhammad Irsyad', '${nama_koordinasi}', True)
print('%-46s -> %-34s %d paragraf' % ('Fadel Muhammad Irsyad', '${nama_koordinasi}', n))

# Nomor ID-nya juga dua kali: koordinasi halaman 1, diketahui halaman konfirmasi
xml, n = ganti(xml, 'ID. 002/SOFTW/XI/2022', '${id_koordinasi}', True)
print('%-46s -> %-34s %d paragraf' % ('ID. 002/SOFTW/XI/2022', '${id_koordinasi}', n))


# --- sel Uraian: seluruh isinya jadi satu penanda ---
# Teksnya memuat tanda pisah en-dash yang berbeda-beda per baris contoh, jadi
# yang dicari nama produknya saja lalu sisa selnya dikosongkan.
TBL_RE = re.compile(r'<w:tbl>.*?</w:tbl>', re.S)
# TR_RE sengaja tidak ada: `<w:tr` tanpa pembatas juga cocok dengan awal
# `<w:trPr>`. Pemotongan baris tabel dikerjakan potong_baris() dengan
# pencarian posisi, yang tidak bisa salah tafsir.
TC_RE = re.compile(r'<w:tc>.*?</w:tc>', re.S)


def teks_sel(sel):
    return unesc(''.join(m.group(2) for m in T_RE.finditer(sel)))


def isi_sel(sel, teks):
    """Taruh `teks` di <w:t> pertama, kosongkan sisanya. Format sel tetap."""
    potongan = list(T_RE.finditer(sel))
    if not potongan:
        return sel
    hasil, posisi = [], 0
    for i, m in enumerate(potongan):
        hasil.append(sel[posisi:m.start()])
        posisi = m.end()
        buka = m.group(1)
        baru_isi = teks if i == 0 else ''
        if baru_isi != baru_isi.strip() and 'xml:space' not in buka:
            buka = buka[:-1] + ' xml:space="preserve">'
        hasil.append(buka + esc(baru_isi) + m.group(3))
    hasil.append(sel[posisi:])
    return ''.join(hasil)



# --- kotak centang status ---
# Tiga pilihan status dicetak berjajar dengan kotak kosong. Kotak mana yang
# tercentang bergantung isian pelaksana, jadi tiap kotak jadi penanda sendiri
# dan aplikasinya mengisi "☒" atau "☐".
KOTAK = '☐'
for penanda in ['${kotak_1}', '${kotak_2}', '${kotak_3}']:
    xml, n = ganti(xml, KOTAK, penanda, False)
    if n == 0:
        print('PERINGATAN: kotak centang untuk %s tidak ketemu' % penanda)
print('kotak centang status: 3 penanda')

# --- kotak catatan/keterangan ---
# Di dokumen aslinya barisnya kosong dengan garis bawah; penanda ditaruh di
# paragraf tepat sesudah labelnya.

tabel = TBL_RE.findall(xml)
rincian = None
for t in tabel:
    if 'Data Lama' in unesc(''.join(m.group(2) for m in T_RE.finditer(t))):
        rincian = t
        break

if rincian is None:
    raise SystemExit('tabel rincian tidak ketemu')

def potong_baris(tabel_xml):
    """Pecah tabel jadi daftar <w:tr>...</w:tr>, tanpa regex.

    Regex non-greedy sempat gagal di sini karena `<w:tr` juga awalan `<w:trPr`.
    Pencarian indeks lebih panjang, tapi tidak bisa salah tafsir."""
    hasil, posisi = [], 0
    while True:
        mulai = tabel_xml.find('<w:tr>', posisi)
        mulai2 = tabel_xml.find('<w:tr ', posisi)
        if mulai < 0 or (0 <= mulai2 < mulai):
            mulai = mulai2
        if mulai < 0:
            break
        tutup = tabel_xml.find('</w:tr>', mulai)
        if tutup < 0:
            break
        tutup += len('</w:tr>')
        hasil.append(tabel_xml[mulai:tutup])
        posisi = tutup
    return hasil


baris = potong_baris(rincian)
print('tabel rincian: %d baris (1 judul + %d contoh)' % (len(baris), len(baris) - 1))

# baris contoh pertama jadi baris template
contoh = baris[1]
sel = TC_RE.findall(contoh)
contoh_baru = contoh
for penanda, isi in zip(sel, ['${no}', '${uraian}', '${data_lama}', '${data_baru}']):
    contoh_baru = contoh_baru.replace(penanda, isi_sel(penanda, isi), 1)

rincian_baru = rincian.replace(contoh, contoh_baru, 1)

# baris contoh sisanya dibuang: TemplateProcessor menggandakan baris template
# sebanyak data, dan baris contoh yang tertinggal akan ikut tercetak apa adanya.
for sisa in baris[2:]:
    rincian_baru = rincian_baru.replace(sisa, '', 1)

xml = xml.replace(rincian, rincian_baru, 1)
print('baris contoh sisa dibuang: %d' % len(baris[2:]))

# tulis docx baru: salin semua entri, ganti document.xml

# --- garis isian di halaman konfirmasi ---
#
# Di dokumen aslinya, "Tanggal Pelaksanaan :" dan "Nama Pelaksana :" diikuti
# garis panjang untuk ditulis tangan. Garis itu bukan teks, melainkan <w:tab/>
# yang melompat ke tab stop ber-leader underscore.
#
# Sekarang kedua nilainya diisi aplikasi, jadi garisnya jadi mengganggu: nilai
# tercetak lalu disusul garis kosong sepanjang sisa baris. Yang kosong tetap
# dapat tempat menulis, tapi berupa titik-titik dari aplikasinya, bukan garis
# yang selalu ada.
# `<w:r[ >]`, bukan `<w:r`: tanpa pembatas itu polanya juga cocok dengan
# awal `<w:rPr>` di dalam `<w:pPr>`, lalu melahap `</w:rPr></w:pPr>` sampai
# `</w:r>` berikutnya. XML-nya tetap terbentuk dan zip-nya tetap sah, tapi
# tagnya tidak lagi berpasangan dan Word menolak membukanya.
RUN_TAB = re.compile(r'<w:r[ >](?:(?!</w:r>).)*?<w:tab/>(?:(?!</w:r>).)*?</w:r>', re.S)


def buang_garis(par):
    return RUN_TAB.sub('', par)


par_semua = P_RE.findall(xml)

# 1. paragraf tanggal & nama pelaksana
for pp in par_semua:
    if '${tanggal_pelaksanaan}' in pp:
        baru = buang_garis(pp)
        print('garis isian dibuang: %d' % (pp.count('<w:tab/>') - baru.count('<w:tab/>')))
        xml = xml.replace(pp, baru, 1)
        break

# 2. dua paragraf garis di bawah "Catatan/Keterangan:"
par_semua = P_RE.findall(xml)
mulai = next(i for i, pp in enumerate(par_semua) if 'Keterangan' in unesc(''.join(m.group(2) for m in T_RE.finditer(pp))))

kosong = [i for i in range(mulai + 1, min(mulai + 4, len(par_semua)))
          if par_semua[i].count('<w:tab/>') == 1
          and unesc(''.join(m.group(2) for m in T_RE.finditer(par_semua[i]))).strip() == '']

if not kosong:
    raise SystemExit('paragraf garis catatan tidak ketemu')

# Yang pertama menampung isi keterangan, sisanya cukup dihapus garisnya:
# keterangan panjang akan membungkus sendiri, dan garis yang tertinggal di
# bawahnya terbaca seperti isian kedua yang belum diisi.
pertama = par_semua[kosong[0]]
isi_pertama = buang_garis(pertama)
sisip = '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t>${keterangan}</w:t></w:r>'
isi_pertama = isi_pertama.replace('</w:p>', sisip + '</w:p>')
xml = xml.replace(pertama, isi_pertama, 1)
print('penanda ${keterangan} dipasang')

for i in kosong[1:]:
    xml = xml.replace(par_semua[i], buang_garis(par_semua[i]), 1)
print('garis catatan sisa dibuang: %d' % len(kosong[1:]))



# --- penanda blok halaman konfirmasi ---
#
# Halaman konfirmasi hanya dicetak setelah pelaksananya menjawab. Sebelum itu
# suratnya berhenti di blok tanda tangan "Disetujui Oleh": halaman kosong berisi
# kotak centang yang belum boleh diisi siapa pun cuma mengundang orang mengisinya
# dengan tangan, di luar sistem.
#
# Dibungkus penanda blok TemplateProcessor: ${konfirmasi} ... ${/konfirmasi},
# masing-masing di paragrafnya sendiri karena deleteBlock() mencari penanda yang
# menutup paragrafnya. Batasnya diambil dari tabel, bukan nomor paragraf: tabel
# terakhir tanda tangan konfirmasi, tabel sebelumnya tanda tangan halaman satu.
PARAGRAF_PENANDA = ('<w:p><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr>'
                    '<w:r><w:t>%s</w:t></w:r></w:p>')

tutup = []
posisi = 0
while True:
    i = xml.find('</w:tbl>', posisi)
    if i < 0:
        break
    tutup.append(i + len('</w:tbl>'))
    posisi = i + 1

if len(tutup) < 2:
    raise SystemExit('tabel tanda tangan tidak ketemu (butuh minimal 2)')

akhir_konfirmasi = tutup[-1]
akhir_ttd_hal1 = tutup[-2]

# Disisipkan dari belakang supaya indeks yang di depan tidak bergeser.
xml = xml[:akhir_konfirmasi] + (PARAGRAF_PENANDA % '${/konfirmasi}') + xml[akhir_konfirmasi:]
xml = xml[:akhir_ttd_hal1] + (PARAGRAF_PENANDA % '${konfirmasi}') + xml[akhir_ttd_hal1:]
print('penanda blok konfirmasi dipasang')


with zipfile.ZipFile(SRC) as zin, zipfile.ZipFile(DST, 'w', zipfile.ZIP_DEFLATED) as zout:
    for item in zin.infolist():
        data = zin.read(item.filename)
        if item.filename == 'word/document.xml':
            data = xml.encode('utf-8')
        zout.writestr(item, data)
print('\ntemplate ditulis:', DST, os.path.getsize(DST), 'byte')
