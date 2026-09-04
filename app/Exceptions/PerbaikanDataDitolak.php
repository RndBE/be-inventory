<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Koreksi data ditolak karena aturannya tidak terpenuhi.
 *
 * Dipisahkan dari Exception biasa supaya pemanggilnya bisa membedakan penolakan
 * yang memang disengaja — kolom di luar daftar putih, nilainya sudah berubah,
 * lotnya sudah terpakai — dari kegagalan tak terduga. Pesannya ditulis untuk
 * dibaca pengguna, bukan untuk log.
 */
class PerbaikanDataDitolak extends RuntimeException
{
}
