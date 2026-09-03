# Panduan Departement Academic

Departement Academic mengelola mahasiswa dan registrasi periode, status akademik, KRS, kurikulum, kelas, mata kuliah, penawaran, jadwal, pertemuan, presensi, serta nilai.

## Masuk dan memilih periode

1. Buka `[alamat-aplikasi]/admin/auth-signin`.
2. Masuk dengan akun **Departement Academic**.
3. Pilih periode akademik pada header.
4. Periksa kode/nama periode pada halaman sebelum membuat atau mengubah data.

Periode tertutup atau arsip bersifat hanya-baca. Jangan memindahkan pekerjaan ke periode lain hanya untuk melewati penguncian.

## Urutan persiapan akademik

Kerjakan dalam urutan berikut agar referensi tersedia:

1. periksa kurikulum dan master/data mata kuliah;
2. buat kelas periode;
3. registrasikan mahasiswa, kelas, dosen wali, status, dan batas SKS;
4. buat penawaran mata kuliah;
5. atur jendela KRS;
6. buat jadwal mingguan dan kalender libur;
7. generate pertemuan;
8. simpan pemeriksaan kesiapan;
9. minta Web Administrator memublikasikan periode.

## Mengelola data mahasiswa

1. Buka **Menu Akademik > Data Mahasiswa**.
2. Cari berdasarkan NIM/nama sebelum menambah data.
3. Klik tambah atau edit.
4. Isi NIM, identitas, program studi, kontak, dan data lain yang diwajibkan.
5. Periksa status akun dan simpan.

Hindari menghapus mahasiswa yang sudah memiliki KRS, nilai, tagihan, atau pembayaran. Gunakan status akun/akademik untuk mempertahankan riwayat.

### Import mahasiswa

1. Unduh export/template contoh.
2. Jangan mengubah nama kolom.
3. Pastikan NIM unik dan referensi program studi/kelas tersedia.
4. Import berkas dan catat jumlah berhasil/gagal.
5. Periksa sampel data hasil impor dan perbaiki baris gagal.

## Registrasi mahasiswa per periode

Registrasi menjadi penghubung mahasiswa dengan kelas, dosen wali, status akademik, dan batas SKS.

1. Pilih periode pada header.
2. Buka data mahasiswa dan pilih mahasiswa.
3. Klik aksi **Registrasi** atau buka bagian registrasi periode.
4. Pilih kelas, dosen wali, status, dan batas SKS.
5. Simpan dan pastikan periode registrasi benar.

Tanpa registrasi periode, mahasiswa tidak dapat menggunakan alur KRS dan fitur akademik periode terkait.

## Mengubah status akademik

1. Buka mahasiswa pada periode yang benar.
2. Pilih status yang tersedia, seperti aktif, cuti, nonaktif, atau lulus.
3. Isi alasan/keterangan jika diminta.
4. Simpan dan periksa riwayat status.

Status menentukan kelayakan mahasiswa mengikuti proses akademik. Gunakan dokumen atau keputusan resmi sebagai dasar perubahan.

## Kenaikan semester massal

1. Buka **Kenaikan Semester**.
2. Pilih periode serta kelas sumber.
3. Pilih periode serta kelas tujuan dan dosen wali tujuan.
4. Tentukan perlakuan setiap kategori status.
5. Klik **Tampilkan Pratinjau**.
6. Periksa mahasiswa yang akan dibuat, dilewati, atau konflik.
7. Koreksi konfigurasi jika hasil belum sesuai.
8. Jalankan proses satu kali, lalu periksa **Audit Proses Terakhir**.

Jangan menjalankan eksekusi tanpa pratinjau. Proses ini membuat banyak registrasi sekaligus.

## Kurikulum, kelas, dan mata kuliah

### Kurikulum

1. Buka **Data Perkuliahan > Data Kurikulum**.
2. Buat kurikulum untuk program studi dan masa berlaku yang sesuai.
3. Gunakan halaman lihat untuk memeriksa mata kuliah yang terhubung.

### Kelas

1. Buka **Data Kelas** pada periode terpilih.
2. Isi nama/kode, program studi, dan program kuliah.
3. Simpan, lalu buka daftar mahasiswa untuk memeriksa peserta.
4. Gunakan cetak jika diperlukan untuk verifikasi.

### Mata kuliah

1. Gunakan master mata kuliah sebagai identitas baku nama dan SKS.
2. Buat data mata kuliah sesuai kurikulum/periode yang digunakan aplikasi.
3. Periksa kode, SKS, semester rekomendasi, dan prasyarat.
4. Hindari duplikasi nama dengan kode berbeda tanpa dasar kurikulum.

## Penawaran mata kuliah

1. Buka halaman **Penawaran Mata Kuliah** dari pengelolaan mata kuliah.
2. Pilih master mata kuliah, program studi, kurikulum, dan kelas.
3. Pilih dosen utama serta dosen pendamping bila ada.
4. Isi prasyarat dan kapasitas.
5. Simpan dan periksa daftar penawaran.
6. Gunakan **Peserta** untuk melihat mahasiswa yang mengambil penawaran.

Setelah dipakai KRS, identitas utama penawaran terkunci. Lakukan verifikasi sebelum jendela KRS dibuka.

### Menyalin penawaran

1. Pilih periode sumber dan tujuan draft.
2. Klik pratinjau penyalinan.
3. Periksa duplikat, referensi yang hilang, dan jumlah data.
4. Eksekusi hanya setelah kelas, kurikulum, dan dosen tujuan siap.
5. Periksa ulang setiap konflik setelah proses.

KRS, nilai, presensi, dan pembayaran tidak ikut disalin.

## Mengatur jendela KRS

1. Buka pengaturan **Jadwal Pengisian KRS**.
2. Isi waktu mulai dan selesai.
3. Centang **Publikasikan** hanya setelah penawaran serta registrasi siap.
4. Simpan dan uji dengan akun mahasiswa yang sesuai bila prosedur institusi memungkinkan.

Sebelum membuka KRS, pastikan batas SKS, dosen wali, prasyarat, kapasitas, serta syarat finance telah diperiksa.

## Mengelola KRS mahasiswa

1. Buka **Kelola KRS Mahasiswa**.
2. Cari registrasi mahasiswa pada periode yang benar.
3. Tinjau status KRS dan total SKS.
4. Tambah/hapus item hanya berdasarkan permintaan atau koreksi resmi.
5. Gunakan **Buka kembali** bila KRS yang terkunci memang perlu diperbaiki.
6. Dokumentasikan alasan perubahan.

Untuk membuka beberapa KRS sekaligus, filter status yang diperlukan, centang KRS pada tabel, pilih **Buka kembali** pada **Aksi massal**, isi alasan minimal 10 karakter, lalu jalankan. Seluruh pilihan harus berada pada periode terpilih dan berstatus diajukan/disetujui/dikunci. Jika satu pilihan tidak valid, seluruh proses dibatalkan.

Academic juga dapat menggunakan **Update KRS melalui Excel**. Gunakan pencarian/filter pada **Pilih mahasiswa** lalu klik **Unduh daftar Excel** untuk mengunduh mahasiswa sesuai hasil filter. Pertahankan kolom `NIM`, `Nama`, dan `Aksi`, lalu isi `buka_kembali` pada setiap baris yang akan diproses; aksi kosong akan diabaikan. Unggah XLSX/CSV, periksa pratinjau, isi alasan minimal 10 karakter, dan jalankan. Maksimal 300 baris beraksi, ukuran 2 MB, dan pratinjau berlaku 15 menit.

Nilai `buka_kembali` membuka KRS berstatus diajukan, disetujui, atau dikunci agar dapat diperbaiki. File unduhan menyertakan kolom **Keterangan Aksi** yang tidak perlu diubah.

## Jadwal mingguan dan pertemuan

1. Buka **Data Jadwal Kuliah/Jadwal Mingguan**.
2. Pilih penawaran, kelas, dosen, ruangan, hari, serta jam mulai/selesai.
3. Simpan; jika bentrok, ubah dosen, kelas, ruang, hari, atau jam.
4. Tambahkan tanggal libur pada **Kalender Hari Libur**.
5. Klik **Pratinjau** generator pertemuan.
6. Isi rentang tanggal dan jumlah pertemuan.
7. Periksa tanggal libur, duplikat, serta jumlah pertemuan.
8. Klik generate/konfirmasi.

Jangan menggunakan pengecualian bentrok tanpa kewenangan Web Administrator dan alasan yang dapat diaudit.

## Presensi dan rekap

1. Buka jadwal lalu halaman presensi.
2. Pilih pertemuan yang benar.
3. Periksa status dan bukti mahasiswa.
4. Perbarui keterangan hanya dengan dasar yang sah.
5. Gunakan cetak daftar hadir atau rekap presensi periode bila diperlukan.

## Nilai mahasiswa

1. Buka mata kuliah/penawaran dan pilih halaman nilai.
2. Cocokkan periode, kelas, dan mahasiswa.
3. Isi komponen nilai sesuai kebijakan.
4. Periksa nilai yang kosong atau di luar rentang.
5. Simpan dan lakukan pemeriksaan sampel.

Koreksi setelah publikasi/penutupan periode harus mengikuti prosedur akademik resmi.

## Pemeriksaan kesiapan periode

1. Buka **Dashboard Pembukaan Periode**.
2. Jalankan pemeriksaan pada periode yang dipilih.
3. Perbaiki kegagalan pada registrasi, kelas, penawaran, dosen, atau jadwal.
4. Simpan snapshot pemeriksaan.
5. Informasikan Web Administrator setelah seluruh komponen akademik siap.

## Checklist sebelum KRS

- [ ] Registrasi, kelas, status, dosen wali, dan batas SKS lengkap.
- [ ] Penawaran, dosen, prasyarat, kapasitas, dan jadwal benar.
- [ ] Jadwal bebas bentrok dan pertemuan sudah dibuat.
- [ ] Periode telah dipublikasikan Web Administrator.
- [ ] Waktu dan status publikasi jendela KRS benar.
- [ ] Finance telah menyiapkan syarat administrasi.

## Kendala umum

| Kendala | Pemeriksaan |
|---|---|
| Mahasiswa tidak dapat KRS | Publikasi periode, registrasi, status, jendela KRS, batas SKS, tagihan, prasyarat, kapasitas. |
| Mata kuliah tidak tampil | Kelas/program studi penawaran, status publikasi, dan periode. |
| Jadwal bentrok | Dosen, kelas, ruangan, hari, serta rentang jam. |
| Jadwal mahasiswa kosong | KRS disetujui, penawaran memiliki jadwal, dan periode dipublikasikan. |
| Data hanya-baca | Status periode sudah ditutup/diarsipkan. |

[Kembali ke daftar panduan](README.md)
