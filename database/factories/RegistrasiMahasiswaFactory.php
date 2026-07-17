<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\RegistrasiMahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrasiMahasiswa>
 */
class RegistrasiMahasiswaFactory extends Factory
{
    protected $model = RegistrasiMahasiswa::class;

    public function definition(): array
    {
        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'taka_id' => TahunAkademik::factory(),
            'semester_mahasiswa' => fake()->numberBetween(1, 14),
            'status_akademik' => RegistrasiMahasiswa::STATUS_AKADEMIK_AKTIF,
            'status_registrasi' => RegistrasiMahasiswa::STATUS_REGISTRASI_TERDAFTAR,
            'kelas_id' => null,
            'dosen_wali_id' => null,
            'batas_sks' => 24,
        ];
    }
}
