# Data Demo Dua Tahun Akademik

Seeder `DemoDuaTahunAkademikSeeder` menyediakan alur empat semester dari tahun akademik 2024/2025 sampai 2025/2026. Seeder bersifat idempoten: aman dijalankan ulang dan hanya memperbarui record berkode `DEMO-*` tanpa menghapus data lain.

Seeder menggunakan fakultas yang sudah tersedia dan tidak membuat fakultas demo baru. Fakultas non-`DEMO-*` dengan ID paling awal diprioritaskan; proses dihentikan dengan pesan yang jelas jika belum ada data fakultas.

## Akun demo

Semua akun memakai kata sandi `Demo123!`.

| Peran | Username | Kondisi demo |
| --- | --- | --- |
| Staf akademik | `demo.akademik` | Publikasi periode dan snapshot kesiapan |
| Staf keuangan | `demo.keuangan` | Template, penerbitan tagihan, dan pembayaran |
| Dosen 1 | `demo.dosen1` | Dosen utama sekaligus dosen wali |
| Dosen 2 | `demo.dosen2` | Dosen utama mata kuliah kedua |
| Mahasiswa Ali | `demo.mahasiswa1` | KRS periode aktif disetujui dan UKT lunas |
| Mahasiswa Siti | `demo.mahasiswa2` | KRS periode aktif masih draft dan UKT pending |

Alamat email memakai domain `.example.test` dan seluruh identitas merupakan data fiktif.

## Cakupan data

- 4 periode akademik, 4 kelas, dan 8 registrasi mahasiswa.
- 8 mata kuliah legacy yang terhubung ke 8 penawaran mata kuliah normalisasi.
- Kalender KRS, perkuliahan, UTS, dan UAS pada setiap periode.
- 8 jadwal mingguan, 32 pertemuan, 16 presensi, tugas, nilai, dan KHS.
- 8 KRS dengan 16 item; tiga semester lama dikunci dan semester aktif memperlihatkan status disetujui serta draft.
- 4 template UKT, 8 tagihan, 8 riwayat pembayaran, dan 4 catatan penerbitan batch.
- Snapshot kesiapan, publikasi, dan audit workflow pada setiap periode.

## Menjalankan ulang

```bash
php artisan academic:purge --preview
php artisan academic:purge --confirm
php artisan db:seed --class=DemoDuaTahunAkademikSeeder --force
```

`--preview` hanya menampilkan jumlah record yang akan dibersihkan. `--confirm` menghapus transaksi akademik, tetapi mempertahankan akun, mahasiswa, dosen, program studi, kurikulum, mata kuliah master, gedung, dan ruang. Buat backup sebelum menjalankan `--confirm`.

Jika terdapat periode aktif non-demo, seeder tidak menonaktifkannya. Periode demo terakhir akan dibuat berstatus ditutup agar tidak mengambil alih konteks periode aktif milik pengguna.
