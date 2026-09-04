<?php

/**
 * Isi tetap Surat Penunjukan Perubahan Data.
 *
 * Sumbernya public/templates/SURAT PENUNJUKAN PERUBAHAN DATA.docx. Yang
 * dipindahkan ke sini hanya bagian yang tidak berasal dari data: pola nomor
 * surat, daftar instruksi, ketentuan pengendalian, dan blok tanda tangan.
 *
 * Diletakkan di config, bukan langsung di blade PDF-nya, karena tiga alasan:
 *
 * - Nama orang di blok tanda tangan berubah kalau ada yang mutasi atau keluar.
 *   Menaruhnya di dalam blade berarti setiap pergantian staf jadi perubahan
 *   kode; di sini cukup satu berkas config. Hardcode nama orang di dalam logika
 *   sudah jadi utang di BahanKeluarController dan tidak perlu ditambah.
 * - Nama dan nomor induk tidak bisa diambil dari tabel `users`: kolom nomor
 *   induk karyawan tidak ada di sana, dan mencocokkan orang lewat nama jabatan
 *   akan mengembalikan orang yang salah begitu ada dua orang berjabatan sama.
 * - Bunyi instruksi dan ketentuan adalah kesepakatan Accounting dengan Software,
 *   bukan perilaku program. Yang mengubahnya tidak seharusnya perlu membaca
 *   blade.
 *
 * Dua slot tanda tangan sengaja TIDAK memakai nama dari sini: "Dibuat Oleh"
 * memakai nama penerbit suratnya, dan "Dilaksanakan Oleh" memakai nama petugas
 * yang mengisi pelaksanaan. Keduanya sudah tercatat di barisnya masing-masing,
 * jadi mengambilnya dari config justru akan mencetak nama yang belum tentu
 * benar. Yang dipakai dari config untuk kedua slot itu hanya label jabatannya.
 *
 * `nama` yang dibiarkan null dicetak sebagai garis tanda tangan kosong untuk
 * diisi tangan — lebih baik daripada mencetak nama yang sudah tidak menjabat.
 */
return [

    'kota' => 'Sleman',

    // Bagian "Nomor : 008/ACC-PD/IX/2026". `kode` adalah bagian tengahnya.
    // Nomor urutnya disimpan per tahun di kolom `nomor_urut`, jadi kode di sini
    // boleh berubah tanpa mengacaukan urutan surat yang sudah terbit.
    'nomor' => [
        'kode' => 'ACC-PD',
        'digit' => 3,
    ],

    'lampiran' => '1 (satu) berkas',
    'perihal' => 'Penunjukan Perubahan Data pada Sistem Inventory',

    // Blok "Kepada Yth."
    'kepada' => [
        'tim' => 'Tim Software',
        'instansi' => 'PT. Arta Teknologi Comunindo',
        'alamat' => 'di Tempat',
    ],

    // Pihak yang menerbitkan surat, dipakai di kalimat pembuka:
    // "dengan ini Accounting menunjuk Tim Software untuk ...".
    'penerbit' => 'Accounting',

    // Nama tim pemohon default, dipakai kalau kolom `tim_pemohon` pada suratnya
    // dibiarkan kosong. Bunyi aslinya "dari Tim Supply Chain".
    'tim_pemohon_default' => 'Tim Supply Chain',

    'instruksi' => [
        'Melakukan perubahan data pada Sistem Inventory sesuai dengan rincian data baru sebagaimana tercantum dalam surat ini.',
        'Memastikan perubahan dilakukan sesuai dengan data dan dokumen pendukung yang telah disetujui.',
        'Tidak melakukan perubahan terhadap data atau transaksi lain di luar rincian yang tercantum dalam permohonan.',
        'Memastikan perubahan tidak menghapus atau mengubah histori transaksi yang tidak berkaitan dengan permohonan tersebut.',
        'Memberikan konfirmasi kepada Accounting setelah perubahan data selesai dilakukan.',
        'Menyampaikan bukti perubahan atau audit trail/log perubahan, apabila fitur tersebut tersedia pada sistem.',
        'Apabila ditemukan ketidaksesuaian antara data pada sistem dengan dokumen permohonan, agar tidak melakukan perubahan terlebih dahulu dan melakukan konfirmasi kepada Accounting.',
    ],

    'ketentuan' => [
        'Surat penunjukan ini merupakan instruksi pelaksanaan perubahan data pada Sistem Inventory berdasarkan permohonan yang telah diajukan dan memperoleh persetujuan dari pihak yang berwenang.',
        'Tim Software bertanggung jawab atas pelaksanaan perubahan data pada sistem sesuai dengan instruksi yang diberikan, sedangkan kebenaran data dan dokumen yang menjadi dasar permohonan merupakan tanggung jawab pihak pemohon dan pihak yang memberikan persetujuan.',
    ],

    'penutup' => 'Demikian surat penunjukan ini dibuat untuk dapat dilaksanakan sebagaimana mestinya. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.',

    // Blok tanda tangan halaman pertama, mengikuti tata letak 3 kolom pada
    // dokumen aslinya.
    'tanda_tangan' => [
        // Baris 1: hanya kolom tengah yang terisi.
        'dibuat' => [
            'label' => 'Dibuat Oleh,',
            'jabatan' => 'Accounting Staff',
            'nama' => null,
            'id' => 'ID. 004/FATSC/VII/2024',
        ],
        // Baris 2.
        'diperiksa_fat' => [
            'label' => 'Diperiksa Oleh,',
            'jabatan' => 'Leader FAT',
            'nama' => 'Dewi Pusporini',
            'id' => 'ID. 003/FATSC/VI/2024',
        ],
        'dikoordinasikan_software' => [
            'label' => 'Diperiksa & Dikoordinasikan Oleh',
            'jabatan' => 'Leader Software',
            'nama' => 'Fadel Muhammad Irsyad',
            'id' => 'ID. 002/SOFTW/XI/2022',
        ],
        // Baris 3, dengan judul "Disetujui Oleh," di kolom tengahnya.
        'disetujui_label' => 'Disetujui Oleh,',
        'disetujui_fat' => [
            'label' => null,
            'jabatan' => 'Manajer FAT',
            'nama' => 'Wahyu Nurul Haryanto',
            'id' => 'ID. 001/FATSC/III/2013',
        ],
        'disetujui_software' => [
            'label' => null,
            'jabatan' => 'Manajer Software',
            'nama' => 'Nofiyanto',
            'id' => 'ID. 001/SOFTW/I/2015',
        ],
    ],

    // Halaman kedua: konfirmasi pelaksanaan.
    //
    // `status` adalah tiga kotak centang pada dokumen aslinya, dan menjadi
    // satu-satunya daftar status pelaksanaan yang diterima aplikasi. Kalau
    // daftar di sini berbeda dari yang bisa dipilih di layar, akan ada surat
    // yang statusnya tidak punya kotak untuk dicentang.
    'konfirmasi' => [
        'judul' => 'KONFIRMASI PELAKSANAAN PERUBAHAN DATA',
        'pernyataan' => 'Dengan ini kami menyatakan bahwa perubahan data pada Sistem Inventory telah dilaksanakan sesuai dengan instruksi dan rincian perubahan yang tercantum dalam surat ini.',
        'status' => [
            'Selesai & Sesuai',
            'Selesai Sebagian',
            'Tidak Dapat Dilaksanakan',
        ],
        'tanda_tangan' => [
            'dilaksanakan' => [
                'label' => 'Dilaksanakan Oleh,',
                'jabatan' => 'Software Staff',
                'nama' => null,
                'id' => '004/SOFTW/XII/2025',
            ],
            'diketahui' => [
                'label' => 'Diketahui Oleh,',
                'jabatan' => 'Leader Software',
                'nama' => 'Fadel Muhammad Irsyad',
                'id' => 'ID. 002/SOFTW/XI/2022',
            ],
        ],
    ],

];
