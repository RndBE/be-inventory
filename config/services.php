<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    /*
     * HRIS — sumber data identitas pegawai (nomor ID, jabatan, divisi).
     *
     * Dipakai saat Berita Acara Serah Terima Aset dibuat, untuk membekukan
     * identitas kedua pihak di dokumennya. Tidak pernah dipanggil saat mencetak:
     * dokumen yang sudah terbit harus tetap sama, dan pencetakan tidak boleh
     * gagal gara-gara HRIS sedang tak bisa dihubungi.
     *
     * timeout sengaja pendek. Pembuatan BAST adalah aksi yang ditunggu pengguna
     * di depan layar, dan kegagalannya sudah ditangani dengan jatuh ke data
     * inventory — menunggu lama tidak memberi manfaat apa pun.
     */
    'hris' => [
        'url' => env('HRIS_URL'),
        'key' => env('HRIS_API_KEY'),
        'timeout' => env('HRIS_TIMEOUT', 5),

        // Berkas CA untuk memverifikasi sertifikat HRIS. Kosongkan di server
        // yang trust store sistemnya sudah benar — itu keadaan normal, dan
        // dibiarkan kosong berarti memakai trust store itu.
        //
        // Diperlukan di lingkungan yang PHP-nya tidak punya `curl.cainfo`,
        // mis. PHP hasil unduh manual di Windows. Tanpa itu setiap panggilan
        // gagal dengan cURL error 60 dan pemanggilnya cuma melihat null.
        // Yang diisi PATH berkas .pem — verifikasi tidak pernah dimatikan.
        'ca' => env('HRIS_CA_BUNDLE'),
    ],

    /*
     * CRM — konsumen data harga modal produk.
     *
     * Key-nya sengaja terpisah dari INVENTORY_API_KEY milik HRIS dan WhatsApp.
     * Endpoint CRM membuka HPP, jadi kalau key-nya bocor harus bisa dicabut
     * tanpa ikut mematikan integrasi lain.
     */
    'crm' => [
        'key' => env('CRM_API_KEY'),
    ],

];
