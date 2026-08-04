{{--
    Pengunci kiriman ganda untuk <form> yang tombol simpannya berada di luar
    pohon form itu — pada halaman aset, tombol Simpan ada di bilah header
    sedangkan form-nya di badan halaman, sehingga tidak terjangkau x-data milik
    form dan tidak bisa dikunci dengan Alpine biasa.

    Tanpa ini, menekan Simpan dua kali mengirim dua permintaan: di halaman
    pengajuan peminjaman itu berarti dua pengajuan kembar untuk aset yang sama.

    Dipasang pada event submit, bukan click tombol. Alasannya:

      - apa pun pemicunya (klik, Enter di kolom teks, submit dari skrip lain)
        tetap tertangkap;
      - event submit TIDAK menyala kalau validasi HTML bawaan gagal, jadi
        tombolnya tidak ikut mati saat form belum lengkap.
--}}
@props(['form', 'label' => 'Menyimpan…'])

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById(@json($form));
        if (!form) return;

        form.addEventListener('submit', function () {
            // form.elements ikut memuat tombol di luar pohon <form> yang
            // terhubung lewat atribut form=, jadi tombol di bilah header pun
            // ikut terkunci.
            Array.prototype.forEach.call(form.elements, function (el) {
                if (el.type !== 'submit') return;

                el.disabled = true;
                // Label hanya ditukar kalau tombolnya murni teks. Tombol
                // berikon akan kehilangan ikonnya kalau textContent ditimpa.
                if (!el.querySelector('svg')) {
                    el.textContent = @json($label);
                }
            });
        });
    });
</script>
