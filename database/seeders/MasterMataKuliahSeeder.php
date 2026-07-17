<?php

namespace Database\Seeders;

use App\Models\MasterMataKuliah;
use Illuminate\Database\Seeder;

class MasterMataKuliahSeeder extends Seeder
{
    public function run(): void
    {
        $curricula = [
            'PAI' => [
                1 => [
                    ['Pend. Kewarganegaraan', 2], ['Bahasa Inggris 1', 2], ['Bahasa Arab 1', 2],
                    ['Pengantar Studi Islam', 2], ['Akhlak Tasawuf', 2], ['ISD/IAD/IBD', 3],
                    ['Sejarah Peradaban Islam', 2], ['Ke-PUI-an', 2], ['Sej. Pendidikan Islam', 2],
                ],
                2 => [
                    ['Bahasa Indonesia', 2], ['Bahasa Inggris 1', 2], ['Bahasa Arab 2', 2],
                    ['Pengantar Ilmu Fiqih', 2], ['Ulumul Qur’an al hadis', 3], ['Dasar-Dasar Pendidikan', 2],
                    ['Ilmu Pendidikan Islam', 2], ['Ilmu Kalam', 2], ['Filsafat Umum', 2],
                ],
                3 => [
                    ['Bahasa Ianggris 3', 2], ['Bahasa arab 3', 2], ["Al-Qur'an/Hadits", 2],
                    ['Tafsir', 2], ['Fiqih', 2], ['Bimbingan Konseling', 2],
                    ['Administrasi Pendidikan', 2], ['iIlmu Jiwa Belajar PAI', 3], ['Pembiayaan pendidikan', 2],
                ],
                4 => [
                    ['Tafsir Tarbawi', 2], ['Ushul Fiqih', 2], ['MKPAI', 3], ['Materi PAI', 2],
                    ['Etika dan Profesi Guru', 2], ['Perbandingan Pendidikan', 2],
                    ['Filsafat Pendidikan Islam', 2], ['Media Pembelajaran PAI', 2],
                    ['Pengembangan Kur. PAI', 2],
                ],
                5 => [
                    ['Perencanaan Pembelajaran', 2], ['Sis. Info & Teknologi Pend', 2], ['Masailul Fiqih', 2],
                    ['Metode Penelitian/PTK', 3], ['Hadist', 2], ['Manaj Pengelolaan Kelas', 2],
                    ['kiroatul Kutub', 2], ['Pengemb Sis Eval PAI', 2], ['Strategi Pembelajaran', 2],
                ],
                6 => [
                    ['Ilmu Mantik', 2], ['Kapita Selekta Pendidikan', 2], ['Mod. Pengmb. Lemb Pend.', 2],
                    ['Analisa Kebijakan Pendidikan', 2], ['Manaj Pengelolaan Kelas', 2], ['Ilmu rosmi', 2],
                    ['Strategi Pembelajaran', 2], ['Statistik Pendidikan', 3], ['Metode Penelitian/PTK', 2],
                ],
                7 => self::semesterTujuh(),
                8 => self::semesterDelapan(),
            ],
            'RA' => [
                1 => [
                    ['Pend. Kewarganegaraan', 2], ['Bahasa Inggris 1', 2], ['Bahasa Arab 1', 2],
                    ['Pengantar Studi Islam', 2], ['Akhlak Tasawuf', 2], ['ISD/IAD/IBD', 3],
                    ['Sejarah Peradaban Islam', 2], ['Ke-PUI-an', 2], ['Sej. Pendidikan Islam', 2],
                ],
                2 => [
                    ['Bahasa Indonesia', 2], ['Bahasa Inggris PAUDNI', 2], ['Bahasa Arab PAUDNI', 2],
                    ['Fiqih', 2], ['Ulumul Qur’an al hadist', 3], ['Ilmu Pendidikan Islam', 2],
                    ['Administrasi Pendidikan', 2], ["Tahsin Al-Qur'an", 2], ['Filsafat Umum', 2],
                ],
                3 => [
                    ['Ushul Fiqih', 2], ['Pembiayaan Pendidikan', 2], ['Bimbingan Peng Ibadah', 2],
                    ['Paedagogic', 3], ['Pengem. Sains di PAUD', 2], ['Bimbingan Konseling Islam', 2],
                    ['Pend. Seni Rupa', 2], ['Ilmu Jiwa Belajar Anak', 2], ['Peng. Anak Usia Dini', 2],
                ],
                4 => [
                    ['Filsapat Pend. Islam', 2], ['Kesehatan dan Gizi', 2], ['Deteksi Dini Tumbuh Kembang', 2],
                    ['Kompetensi Propesi PAUD', 2], ['Bermain dan permainan', 2], ['Pengemb fisik/Motorik', 3],
                    ['Pengembangan sosial,emosi', 2], ['Media pembelajaran RA', 2],
                    ['Pengembangan Kurikulum', 2],
                ],
                5 => [
                    ['Perencanaan Pembelajaran', 2], ['Pend. Anak Berkebutuhan Khusus+E80', 2],
                    ['Matematika Anak Usia Dini', 2], ['Metode Penelitian/PTK', 3],
                    ['Pembel. Anak Usia Dini', 2], ['Manaj Penyeleng PAUDNI', 2],
                    ['Pengeb Seni Tari, Musik', 2], ['Peng Eval Pembel RA', 2],
                    ['Strategi Pembelajaran PAUD', 2],
                ],
                6 => [
                    ['Pengembangan Kur. PAUDNI', 2], ['Kapita Selekta Pend PAUD', 2],
                    ['Mod. Pengmb. Lemb Pend.', 2], ['Perlindungan Hak Anak', 2],
                    ['Manaj Pengelolaan Kelas', 2], ['Ilmu rosmi', 2], ['Strategi Pembelajaran PAUD', 2],
                    ['Statistik Pendidikan', 3], ['Pemb al-quran anak PAUD', 2],
                ],
                7 => self::semesterTujuh(),
                8 => self::semesterDelapan(),
            ],
            'MI' => [
                1 => [
                    ['Pend. Kewarganegaraan', 2], ['Bahasa Inggris 1', 2], ['Bahasa Arab 1', 2],
                    ['Pengantar Studi Islam', 2], ['Akhlak Tasowuf', 2], ['ISD/IAD/IBD', 3],
                    ['Sejarah Peradaban Islam,', 2], ['Ke-PUI-an', 2], ['Sej. Pendidikan Islam', 2],
                ],
                2 => [
                    ['Bahasa Indonesia', 2], ['Bahasa Inggris', 2], ['Ilmu Kalam', 2], ['Fiqih', 2],
                    ['Ulumul Qur’an alhadist', 3], ['Ilmu Pendidikan Islam', 2], ['Psikologi Umum', 2],
                    ['Tahfidz Al-Qur;an/Hadits', 2], ['Filsafat Umum', 2],
                ],
                3 => [
                    ['Ushul Fiqih', 2], ['Pend. Keterampilan Tangan', 3], ['Pendidikan kesehatan Anak', 2],
                    ['Pendidikan kesenian', 2], ['Bahasa Daerah', 2], ['Bimbingan Konseling', 2],
                    ['Sosisologi Pendidikan', 2], ['Ilmu Jiwa Belajar', 2], ['Psikologi Anak', 2],
                ],
                4 => [
                    ['Hadits Tarbawi', 2], ['Matematika 1', 2], ['Pembelajaran IPS MI', 2],
                    ['Manaj Pengelolaan Kelas', 2], ['Model Pembelajaran Tematik', 2],
                    ['Pend. Kepribadian Anak', 3], ['Pengeb. Bakat dan Kreativitas', 2],
                    ['Pengembangan sumber belajar MI', 2], ['Pengembangan Kur MI', 2],
                ],
                5 => [
                    ['Perencanaan Pembelajaran', 2], ['Pend. Olahraga', 2], ['Pembelajaran Bahasa Inggris', 2],
                    ['Metode Penelitian/PTK', 3], ['Etika dan Profesi Guru', 2], ['Pembelajaran PPKN', 2],
                    ['Pengeb Seni Tari, Musik', 2], ['Pengp Sis Eval Pemb MI', 2],
                    ['Ket. Menulis & Menggambar', 2],
                ],
                6 => [
                    ['Pembelajaran IPA', 2], ['Pembelajaran Matematika', 2], ['Pembelajaran SKI', 2],
                    ['Pembelajaran IPS', 2], ['Pembelajaran Aqidah Akhlak', 2], ['Pembelajaran Bhs Indo', 2],
                    ['Pembelajaran Bhs Arab', 2], ['Pembelajaran Fiqih', 2], ['Statistik Pendidikan', 3],
                ],
                7 => self::semesterTujuh(),
                8 => self::semesterDelapan(),
            ],
            'PBA' => [
                1 => [
                    ['Pend. Kewarganegaraan', 2], ['Bahasa Inggris 1', 2], ['Nahwu 1', 2],
                    ['Pengantar Studi Islam', 2], ['Akhlak Tasawuf', 2], ['ISD/IAD/IBD', 3],
                    ['Sejarah Peradaban Islam', 2], ['Ke-PUI-an', 2], ['Sej. Pendidikan Islam', 2],
                ],
                2 => [
                    ['Bahasa Indonesia', 2], ['Bahasa Inggris', 2], ['Ilmu Kalam', 2], ['Fiqih Lughoh', 2],
                    ['Ulumul Qur’an al hadist', 3], ['Ilmu Pendidikan Islam', 2], ['Nahwu 2', 2],
                    ['Sharaf 1', 2], ['Filsafat Umum', 2],
                ],
                3 => [
                    ['Ushul Fiqih', 1], ['Nahwu 3', 2], ['Sharaf 2', 2], ['Insya 1', 2],
                    ['Pembiayayan Pendidikan', 2], ['Ilmu Jiwa Belajar PBA', 2],
                    ['Administrasi Pembelajaran', 2], ["Muthola'ah 1", 2], ['Tafsir', 2],
                ],
                4 => [
                    ['Balaghah 1', 2], ["Muthola'ah 2", 2], ['Met. Pembelajaran B Arab', 3],
                    ['Ilmu lughoh / Linguistik', 2], ['Keterampilan bahasa', 4], ['Insya 2', 2],
                    ['Psikologi Pendidikan', 3], ['Shorof 3', 2],
                ],
                5 => [
                    ['Perencanaa Pembelajaran', 2], ['Balaghah 2', 2], ['Insya 3', 2],
                    ['Metode Penelitian/PTK', 3], ['Muhadasah 1', 2], ['Manaj Pengelolaan Kelas', 2],
                    ['Ilmu rosmi', 2], ['Pengemb Eval Pembl PBA', 2], ['Media pembelajaran', 2],
                ],
                6 => [
                    ['Muhadasah 2', 2], ['Insya 2', 2], ['Tarjim', 4], ['Ilmu Mantik', 2],
                    ['Lugoh Al-Jarald Wal Majalah', 2], ['Statistik Pendidikan', 3],
                    ['Etika dan Profesi Guru', 2], ['Pengembangan Kurikulum', 2],
                ],
                7 => self::semesterTujuh(),
                8 => self::semesterDelapan(),
            ],
        ];

        $now = now();
        $rows = [];

        foreach ($curricula as $programStudi => $semesters) {
            foreach ($semesters as $semester => $mataKuliahs) {
                foreach ($mataKuliahs as [$name, $sks]) {
                    $rows[] = [
                        'program_studi' => $programStudi,
                        'semester' => $semester,
                        'name' => $name,
                        'sks' => $sks,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        MasterMataKuliah::upsert(
            $rows,
            ['program_studi', 'semester', 'name'],
            ['sks', 'updated_at'],
        );
    }

    private static function semesterTujuh(): array
    {
        return [
            ['Kewirausahaan', 2],
            ['microteaching', 4],
            ['Pramuka', 2],
            ['PPK', 7],
            ['KKM', 4],
        ];
    }

    private static function semesterDelapan(): array
    {
        return [
            ['SKRIPSI', 4],
            ['Komprehensif', 4],
            ['MUNAQOSYAH', 2],
        ];
    }
}
