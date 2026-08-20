<?php

namespace App\Models;

/**
 * @deprecated Gunakan PeriodeAkademik. Nama ini dipertahankan agar integrasi
 * legacy tetap kompatibel dengan tabel `tahun_akademiks` dan kolom `taka_id`.
 */
class TahunAkademik extends PeriodeAkademik
{
    // Alias kompatibilitas untuk nama domain lama.
}
