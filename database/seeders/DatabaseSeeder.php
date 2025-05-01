<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Str;
Use Hash;
use Carbon\Carbon;
// DEFAULT AUTHENTIKASI
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // SEEDER KHUSUS DATA MASTER AKADEMIK
        \App\Models\Fakultas::create([
            'name'       => 'Fakultas Tarbiyah',
            'code'       => 'FPAI',
            'head_id'    => '1',
        ]);
        \App\Models\ProgramStudi::create([
            'name'       => 'Pendidikan Guru Pendidikan Agama Islam',
            'code'       => 'PGPAI',
            'cnim'       => '4210',
            'title'      => ', S.Pd',
            'level'      => 'S1',
            'slug'       => Str::slug('Pendidikan Guru Pendidikan Agama Islam'),
            'head_id'    => '1',
            'faku_id'    => '1',
        ]);
        \App\Models\ProgramStudi::create([
            'name'       => 'Pendidikan Guru Bahasa Arab',
            'code'       => 'PGBA',
            'cnim'       => '4211',
            'title'      => ', S.Pd',
            'level'      => 'S1',
            'slug'       => Str::slug('Pendidikan Guru Bahasa Arab'),
            'head_id'    => '1',
            'faku_id'    => '1',
        ]);
        \App\Models\TahunAkademik::create([
            'name'       => 'TA. 2025/2026',
            'code'       => '012023',
            'semester'   => '1',
            'year_start' => '2025',
        ]);
        \App\Models\TahunAkademik::create([
            'name'       => 'TA. 2025/2026',
            'code'       => '022023',
            'semester'   => '2',
            'year_start' => '2025',
        ]);
        \App\Models\ProgramKuliah::create([
            'name'       => 'Regular Sore',
            'code'       => 'G1RP-2023',
            'wave'       => 'Gelombang I',
            'taka_id'    => '1',
            'pstudi_id'  => '1',
        ]);
        \App\Models\Kurikulum::create([
            'name'       => 'Kurikulum 2020',
            'code'       => 'K20',
            'desc'       => 'Kurikulum 2020 adalah kurikulum dirancang 25 Tahun',
            'year_start' => '2019',
            'year_ended' => '2025',
        ]);
        \App\Models\MataKuliah::create([
            'name'       => 'Pendidikan Bahasa Arab 1',
            'code'       => 'PBA1',
            'desc'       => 'Matakuliah yang membahas mengenai pendidikan bahasa arab',
            'bsks'       => '20',
            'kuri_id'    => '1',
            'taka_id'    => '1',
            'dosen_1'    => '1',
            'dosen_2'    => '2',
            'pstudi_id'  => '1',
        ]);
        \App\Models\MataKuliah::create([
            'name'       => 'Sejarah Peradaban Islam',
            'code'       => 'SPI',
            'desc'       => 'Matakuliah yang membahas mengenai sejarah peradaban islam',
            'bsks'       => '20',
            'kuri_id'    => '1',
            'taka_id'    => '1',
            'dosen_1'    => '2',
            'pstudi_id'  => '1',
        ]);
        \App\Models\Kelas::create([
            'name'       => 'PBA-2025-1A',
            'code'       => 'PBA-2025-1A',
            'capacity'   => '32',
            'dosen_id'   => '1',
            'proku_id'   => '1',
            'taka_id'    => '1',
            'pstudi_id'  => '2',
        ]);
        \App\Models\Kelas::create([
            'name'       => 'PAI-2025-1A',
            'code'       => 'PAI-2025-1A',
            'capacity'   => '32',
            'dosen_id'   => '2',
            'proku_id'   => '1',
            'taka_id'    => '1',
            'pstudi_id'  => '1',
        ]);
        \App\Models\JadwalKuliah::create([
            'makul_id'  => '1',
            'kelas_id'  => '1',
            'dosen_id'  => '1',
            'ruang_id'  => '1',
            'pert_id'  => '1',
            'meth_id'  => '1',
            'days_id'  => '1',
            'bsks'  => '3',
            'code'  => Str::random(8),
            'date'  => now()->format('Y-m-d'),
            'start'  => '01:00:00',
            'ended'  => '23:00:00',

        ]);
        \App\Models\JadwalKuliah::create([
            'makul_id'  => '1',
            'kelas_id'  => '2',
            'dosen_id'  => '1',
            'ruang_id'  => '1',
            'pert_id'  => '1',
            'meth_id'  => '1',
            'days_id'  => '1',
            'bsks'  => '3',
            'code'  => Str::random(8),
            'date'  => now()->format('Y-m-d'),
            'start'  => '01:00:00',
            'ended'  => '23:00:00',

        ]);
        \App\Models\JadwalKuliah::create([
            'makul_id'  => '1',
            'kelas_id'  => '2',
            'dosen_id'  => '1',
            'ruang_id'  => '1',
            'pert_id'  => '2',
            'meth_id'  => '1',
            'days_id'  => '1',
            'bsks'  => '3',
            'code'  => Str::random(8),
            'date'  => now()->format('Y-m-d'),
            'start'  => '01:00:00',
            'ended'  => '23:00:00',

        ]);
        \App\Models\JadwalKuliah::create([
            'makul_id'  => '1',
            'kelas_id'  => '1',
            'dosen_id'  => '1',
            'ruang_id'  => '1',
            'pert_id'  => '2',
            'meth_id'  => '1',
            'days_id'  => '1',
            'bsks'  => '3',
            'code'  => Str::random(8),
            'date'  => now()->format('Y-m-d'),
            'start'  => '01:00:00',
            'ended'  => '23:00:00',

        ]);

        // SEEDER KHUSUS DATA MASTER INVENTARIS
        \App\Models\Gedung::create([
            'name'       => 'Area Luar Kampus',
            'code'       => 'ALK',
        ]);
        \App\Models\Gedung::create([
            'name'       => 'Area Sekitar Gedung',
            'code'       => 'ASG',
        ]);
        \App\Models\Gedung::create([
            'name'       => 'Gedung A',
            'code'       => 'GDA',
        ]);
        \App\Models\Ruang::create([
            'gedu_id'    => '3',
            'floor'      => '1',
            'type'       => '1',
            'name'       => 'Kelas 101',
            'code'       => 'C-101',
        ]);
        \App\Models\Ruang::create([
            'gedu_id'    => '3',
            'floor'      => '1',
            'type'       => '1',
            'name'       => 'Kelas 102',
            'code'       => 'C-102',
        ]);
        \App\Models\Ruang::create([
            'gedu_id'    => '3',
            'floor'      => '1',
            'type'       => '1',
            'name'       => 'Kelas 103',
            'code'       => 'C-103',
        ]);

        // TAGIHAN KULIAH
        \App\Models\TagihanKuliah::create([
            'users_id'    => '1',
            'name'    => 'UKT Semester 1',
            'code'    => 'UKT-'.Str::random(8),
            'price'    => '2400000',
        ]);
        // TAGIHAN KULIAH
        \App\Models\TagihanKuliah::create([
            'proku_id'    => '1',
            'name'    => 'UKT Semester 2',
            'code'    => 'UKT-'.Str::random(8),
            'price'    => '2200000',
        ]);
        // DEFAULT TUGAS SEEDER
        \App\Models\studentTask::create([
            'dosen_id'    => '1',
            'jadkul_id'    => '1',
            'code'    => Str::random(8),
            'title'    => 'First Task',
            'detail_task'    => 'First Task Deskription',
            'exp_date'  => Carbon::now()->addDays(7),
            'exp_time'  => Carbon::now()->addHours(12),
        ]);
        \App\Models\studentTask::create([
            'dosen_id'    => '1',
            'jadkul_id'    => '2',
            'code'    => Str::random(8),
            'title'    => 'First Task',
            'detail_task'    => 'First Task Deskription',
            'exp_date'  => Carbon::now()->addDays(7),
            'exp_time'  => Carbon::now()->addHours(12),
        ]);
        \App\Models\studentTask::create([
            'dosen_id'    => '1',
            'jadkul_id'    => '3',
            'code'    => Str::random(8),
            'title'    => 'Second Task',
            'detail_task'    => 'Second Task Deskription',
            'exp_date'  => Carbon::now()->addDays(7),
            'exp_time'  => Carbon::now()->addHours(12),
        ]);
        \App\Models\studentTask::create([
            'dosen_id'    => '1',
            'jadkul_id'    => '4',
            'code'    => Str::random(8),
            'title'    => 'Second Task',
            'detail_task'    => 'Second Task Deskription',
            'exp_date'  => Carbon::now()->addDays(7),
            'exp_time'  => Carbon::now()->addHours(12),
        ]);

        // Kategori Berita
        $categories = [
            ['name' => 'Teknologi', 'desc' => 'Berita seputar teknologi'],
            ['name' => 'Bisnis', 'desc' => 'Berita seputar dunia bisnis'],
            ['name' => 'Kesehatan', 'desc' => 'Berita seputar kesehatan'],
            ['name' => 'Sains', 'desc' => 'Berita seputar ilmu pengetahuan'],
            ['name' => 'Hiburan', 'desc' => 'Berita seputar dunia hiburan'],
            ['name' => 'Olahraga', 'desc' => 'Berita seputar olahraga'],
            ['name' => 'Politik', 'desc' => 'Berita seputar politik'],
            ['name' => 'Mode', 'desc' => 'Berita seputar dunia mode'],
            ['name' => 'Travel', 'desc' => 'Berita seputar perjalanan'],
            ['name' => 'Makanan', 'desc' => 'Berita seputar makanan'],
            ['name' => 'Pendidikan', 'desc' => 'Berita seputar dunia pendidikan'],
            ['name' => 'Lingkungan', 'desc' => 'Berita seputar lingkungan'],
            ['name' => 'Gaya Hidup', 'desc' => 'Berita seputar gaya hidup'],
            ['name' => 'Opini', 'desc' => 'Opini dan pandangan'],
            ['name' => 'Cuaca', 'desc' => 'Berita seputar cuaca'],
            ['name' => 'Seni', 'desc' => 'Berita seputar seni'],
            ['name' => 'Film', 'desc' => 'Berita seputar dunia film'],
            ['name' => 'Musik', 'desc' => 'Berita seputar musik'],
            ['name' => 'Buku', 'desc' => 'Berita seputar dunia literatur'],
            ['name' => 'Ekonomi', 'desc' => 'Berita seputar ekonomi'],
        ];

        foreach ($categories as $category) {
            \App\Models\newsCategory::create([
                'name' => $category['name'],
                'code' => Str::random(6),
                'slug' => Str::slug($category['name']),
                'desc' => $category['desc'],
            ]);
        }

        \App\Models\newsPost::create([
            'category_id'    => '1',
            'author_id'    => '1',
            'name'    => 'Sample Post First',
            'code'    => Str::random(6),
            'slug'    => 'sample-post-first',
            'image'    => 'default/default-profile.jpg',
            'metadesc'    => 'Meta Descriptsion Sample',
            'keywords'    => 'Keywords 1, Keywords 2, Keywords 3',
            'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Tenetur, omnis quibusdam aliquam at nemo repellat nam ad adipisci itaque alias eveniet consequuntur molestiae cupiditate dolores, id magni autem vero quam, suscipit nulla facere molestias ipsum? Adipisci, animi natus. Modi, veniam doloribus assumenda in dolorem exercitationem quaerat tempora non temporibus magni earum voluptatibus autem quibusdam tempore voluptas aperiam, consequuntur alias fuga laudantium sed harum distinctio repudiandae facere omnis. Sint sunt dignissimos fugit velit voluptatibus adipisci esse minima explicabo. Nisi est architecto quasi suscipit amet quaerat nulla dolore illo quis inventore, error iusto nostrum eaque nemo, atque odio quas esse aut aperiam!',
        ]);

        \App\Models\newsPost::create([
            'category_id'    => '2',
            'author_id'    => '1',
            'name'    => 'Sample Post Second',
            'code'    => Str::random(6),
            'slug'    => 'sample-post-second',
            'image'    => 'default/default-profile.jpg',
            'metadesc'    => 'Meta Descriptsion Sample',
            'keywords'    => 'Keywords 1, Keywords 2, Keywords 3',
            'content' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Tenetur, omnis quibusdam aliquam at nemo repellat nam ad adipisci itaque alias eveniet consequuntur molestiae cupiditate dolores, id magni autem vero quam, suscipit nulla facere molestias ipsum? Adipisci, animi natus. Modi, veniam doloribus assumenda in dolorem exercitationem quaerat tempora non temporibus magni earum voluptatibus autem quibusdam tempore voluptas aperiam, consequuntur alias fuga laudantium sed harum distinctio repudiandae facere omnis. Sint sunt dignissimos fugit velit voluptatibus adipisci esse minima explicabo. Nisi est architecto quasi suscipit amet quaerat nulla dolore illo quis inventore, error iusto nostrum eaque nemo, atque odio quas esse aut aperiam!',
        ]);

        \App\Models\Settings\webSettings::create([
            'school_apps' => 'Siakad v1.0 ',
            'school_name' => 'STAI PUI',
            'school_head' => 'Dr. H. Agus Sutisna, M.Pd.',
            'school_desc' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Tenetur, omnis quibusdam aliquam at nemo repellat nam ad adipisci itaque alias eveniet consequuntur molestiae cupiditate dolores, id magni autem vero quam, suscipit nulla facere molestias ipsum? Adipisci, animi natus. Modi, veniam doloribus assumenda in dolorem exercitationem quaerat tempora non temporibus magni earum voluptatibus autem quibusdam tempore voluptas aperiam, consequuntur alias fuga laudantium sed harum distinctio repudiandae facere omnis. Sint sunt dignissimos fugit velit voluptatibus adipisci esse minima explicabo. Nisi est architecto quasi suscipit amet quaerat nulla dolore illo quis inventore, error iusto nostrum eaque nemo, atque odio quas esse aut aperiam!',
            'school_link' => '',
            'school_email' => 'koacime@gmail.com',
            'school_phone' => '+6212334567890',
            'social_ig' => '#',
            'social_fb' => '#',
            'social_in' => '#',
            'social_tw' => '#',
        ]);

        $this->call([
            GalleryAlbumSeeder::class,
            UserSeeder::class,
        ]);

    }
}
