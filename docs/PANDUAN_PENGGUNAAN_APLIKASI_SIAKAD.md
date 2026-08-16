# PANDUAN PENGGUNAAN APLIKASI SIAKAD

**Sistem Informasi Akademik**  
**Versi dokumen:** 1.0  
**Tanggal:** 18 Juli 2026  
**Sasaran pengguna:** Web Administrator, staf, dosen, dan mahasiswa

---

> Panduan yang lebih rinci dan terpisah untuk setiap jenis pengguna tersedia pada [indeks panduan pengguna per peran](panduan-pengguna/README.md).

## Informasi Dokumen

Panduan ini menjelaskan penggunaan aplikasi SIAKAD dari sisi pengguna. Nama tombol atau menu mengikuti tampilan aplikasi. Menu yang terlihat pada setiap akun dapat berbeda sesuai peran, status akun, dan periode akademik yang sedang dipilih.

Untuk keamanan, panduan ini tidak memuat nama pengguna, kata sandi, kunci pembayaran, atau alamat server tertentu. Ganti `[alamat-aplikasi]` dengan alamat SIAKAD resmi institusi.

## Daftar Isi

1. Mengenal aplikasi dan hak akses
2. Persiapan dan cara masuk
3. Navigasi umum
4. Halaman publik
5. Fitur umum staf
6. Panduan Web Administrator
7. Panduan Departemen Akademik
8. Panduan Departemen Finance
9. Panduan Departemen Support, Officer, dan Admin
10. Panduan dosen
11. Panduan mahasiswa
12. Alur operasional satu periode akademik
13. Pemecahan masalah
14. Keamanan dan praktik penggunaan yang baik
15. Glosarium
16. Checklist operasional

---

# 1. Mengenal Aplikasi dan Hak Akses

SIAKAD digunakan untuk mengelola data pengguna, akademik, kegiatan belajar mengajar, keuangan, publikasi, dan layanan bantuan. Aplikasi juga menyediakan portal khusus dosen dan mahasiswa.

## 1.1 Kelompok pengguna

| Pengguna | Ruang lingkup utama |
|---|---|
| Web Administrator | Seluruh konfigurasi utama, pengguna, akademik, inventaris, keuangan, publikasi, pengaturan situs, dan publikasi periode |
| Departement Academic | Mahasiswa, registrasi periode, status akademik, kurikulum, kelas, mata kuliah, penawaran, jadwal, nilai, dan kesiapan periode |
| Departement Finance | Template dan penerbitan tagihan, pembayaran, kas/keuangan, administrasi KRS, dan persetujuan absensi staf |
| Departement Support | Data gedung dan ruangan serta fitur umum staf |
| Departement Officer | Fitur umum staf dan publikasi sesuai hak akses yang diberikan |
| Departement Admin | Fitur umum staf, publikasi, dan pemeriksaan kesiapan sesuai hak akses yang diberikan |
| Dosen | Persetujuan KRS mahasiswa bimbingan, jadwal mengajar, presensi, umpan balik, tugas, dan penilaian tugas |
| Mahasiswa | KRS, jadwal, presensi, tugas, nilai, tagihan, pembayaran, profil, dan ticket support |
| Pengunjung | Berita, pengumuman, album foto, dokumen, informasi program, serta saran dan masukan |

> **Catatan:** akun yang tidak aktif atau tidak memiliki peran yang sesuai akan ditolak ketika membuka fitur tertentu. Hubungi Web Administrator jika menu yang seharusnya tersedia tidak muncul.

---

# 2. Persiapan dan Cara Masuk

## 2.1 Persiapan

Gunakan peramban versi terbaru seperti Chrome, Edge, Firefox, atau Safari. Pastikan koneksi internet stabil, terutama saat mengunggah berkas tugas, bukti presensi, data impor, atau melakukan pembayaran.

Siapkan alamat portal sesuai jenis akun:

| Jenis akun | Alamat masuk |
|---|---|
| Staf/Web Administrator | `[alamat-aplikasi]/admin/auth-signin` |
| Dosen | `[alamat-aplikasi]/dosen/auth-signin` |
| Mahasiswa | `[alamat-aplikasi]/mahasiswa/auth-signin` |

## 2.2 Masuk ke aplikasi

1. Buka alamat masuk sesuai jenis akun.
2. Isi identitas akun:
   - staf: username, nomor telepon, atau email;
   - dosen: NIDN, nomor telepon, atau email;
   - mahasiswa: NIM atau email.
3. Masukkan kata sandi.
4. Aktifkan **Ingat saya** hanya pada perangkat pribadi.
5. Klik **Masuk ke portal**.

Jika berhasil, aplikasi mengarahkan pengguna ke halaman **Home** sesuai perannya.

## 2.3 Lupa kata sandi

1. Pada halaman masuk, klik **Lupa kata sandi?**.
2. Masukkan email yang terdaftar.
3. Ikuti tautan pengaturan ulang yang dikirimkan ke email.
4. Isi kata sandi baru dan konfirmasinya.
5. Kembali ke halaman masuk dan gunakan kata sandi baru.

Jika email tidak diterima, periksa folder spam. Bila alamat email akun sudah tidak aktif, minta administrator memperbaruinya.

## 2.4 Keluar dari aplikasi

1. Klik nama atau foto pengguna di kanan atas.
2. Pilih **Logout**.
3. Tutup peramban bila menggunakan komputer bersama.

---

# 3. Navigasi Umum

## 3.1 Bagian layar

- **Sidebar kiri** berisi menu utama sesuai peran.
- **Header atas** menampilkan judul halaman, notifikasi, ticket support, pemilih periode untuk staf, dan menu akun.
- **Area konten** menampilkan formulir, tabel, tombol aksi, serta pesan berhasil atau gagal.
- Ikon **burger** di kiri atas digunakan untuk membuka atau menutup sidebar pada layar kecil.

## 3.2 Memilih konteks periode akademik bagi staf

Pemilih periode berada di header atas. Pilihan ini menentukan periode yang digunakan pada halaman akademik dan keuangan.

1. Klik daftar **Periode akademik** di header.
2. Pilih kode periode yang akan dikerjakan.
3. Tunggu halaman dimuat ulang.
4. Periksa kembali nama/kode periode pada judul halaman sebelum mengubah data.

Label **(Aktif)** menunjukkan periode aktif institusi. Staf masih dapat memilih periode lain untuk melihat data historis, tetapi periode tertutup atau arsip bersifat hanya-baca.

> **Penting:** selalu periksa periode sebelum menambah kelas, penawaran, jadwal, status mahasiswa, atau tagihan. Kesalahan memilih periode dapat membuat data tercatat pada semester yang salah.

## 3.3 Profil pengguna

1. Klik nama atau foto di kanan atas.
2. Pilih **My Profile**.
3. Gunakan bagian yang tersedia untuk memperbarui foto, data pribadi, kontak, atau keamanan.
4. Klik tombol simpan pada masing-masing bagian.

Untuk mengganti kata sandi, isi kata sandi lama, kata sandi baru, dan konfirmasi kata sandi baru. Gunakan kata sandi yang kuat dan tidak digunakan pada layanan lain.

## 3.4 Notifikasi

Klik ikon lonceng di header untuk melihat pemberitahuan terbaru. Klik salah satu pemberitahuan bila tersedia tautan menuju halaman terkait.

## 3.5 Tabel dan aksi data

Halaman pengelolaan umumnya menyediakan tombol **Tambah**, **Edit**, **Hapus**, **Lihat**, **Cetak**, **Import**, atau **Export**. Perhatikan hal berikut:

- data yang sudah dipakai transaksi mungkin tidak dapat dihapus;
- beberapa perubahan dikunci setelah periode diterbitkan atau ditutup;
- baca pesan konfirmasi sebelum menjalankan tindakan massal;
- lakukan pratinjau jika aplikasi menyediakannya.

---

# 4. Halaman Publik

Pengunjung tidak perlu masuk untuk menggunakan halaman publik.

## 4.1 Informasi dan publikasi

Dari halaman utama, pengunjung dapat membuka berita/pengumuman, album foto, dokumen unduhan, informasi program studi, dan informasi program kuliah. Gunakan menu situs atau klik judul konten untuk melihat rincian.

## 4.2 Album foto

1. Buka menu **Album Foto**.
2. Gunakan pencarian bila diperlukan.
3. Pilih album untuk melihat isi galeri.

## 4.3 Dokumen unduhan

1. Buka menu **Download**.
2. Cari dokumen yang dibutuhkan.
3. Klik tautan unduh dan simpan berkas.

## 4.4 Saran dan masukan

1. Buka halaman **Saran/Kritik**.
2. Isi identitas dan isi pesan sesuai kolom yang tersedia.
3. Kirim formulir.

Jangan memasukkan kata sandi, data pembayaran, atau dokumen pribadi ke dalam kotak saran.

---

# 5. Fitur Umum Staf

Fitur berikut tersedia pada portal staf, dengan cakupan sesuai hak akses akun.

## 5.1 Dashboard

Menu **Home** menampilkan ringkasan data dan pemberitahuan. Gunakan dashboard untuk memastikan akun aktif serta konteks periode telah sesuai.

## 5.2 Data pemberitahuan

1. Buka **Menu Publikasi > Data Pemberitahuan**.
2. Klik tombol tambah untuk membuat pemberitahuan.
3. Isi judul, deskripsi, sasaran penerima, dan tautan bila diperlukan.
4. Simpan.
5. Gunakan **Edit** atau **Hapus** untuk mengelola pemberitahuan lama.

Pilih sasaran dengan hati-hati agar pemberitahuan hanya diterima kelompok yang berkepentingan.

## 5.3 Berita dan kategori

### Membuat kategori

1. Buka **Menu Publikasi > Data Berita > Kategori Berita**.
2. Tambahkan nama kategori.
3. Simpan.

### Membuat berita

1. Buka **Menu Publikasi > Data Berita > Berita**.
2. Klik **Tambah/Buat Berita**.
3. Isi judul, kategori, gambar, dan isi berita.
4. Simpan dan periksa hasilnya melalui tombol lihat atau halaman publik.

Pastikan materi publikasi tidak mengandung data pribadi yang tidak boleh diumumkan.

## 5.4 Album foto

1. Buka **Menu Publikasi > Data Album Foto**.
2. Klik **Buat Album**.
3. Isi judul dan keterangan album, kemudian unggah foto.
4. Simpan.
5. Gunakan halaman detail untuk memeriksa isi album.

## 5.5 Dokumen

1. Buka **Menu Publikasi > Data Document**.
2. Klik **Tambah Dokumen**.
3. Isi informasi dokumen dan unggah berkas.
4. Simpan.

Gunakan nama dokumen yang jelas dan pastikan berkas final sebelum dipublikasikan.

---

# 6. Panduan Web Administrator

Web Administrator memiliki akses terluas dan bertanggung jawab atas konfigurasi lintas departemen.

## 6.1 Mengelola akun pengguna

Menu **Data Pengguna** terdiri dari Data Admin, Data Pegawai, Data Dosen, dan Data Mahasiswa.

### Menambah akun

1. Buka kelompok pengguna yang sesuai.
2. Klik **Tambah**.
3. Isi identitas, kontak, status akun, dan data keamanan.
4. Untuk staf, pilih departemen/hak akses yang benar.
5. Simpan.

### Mengubah atau menonaktifkan akun

1. Cari pengguna pada tabel.
2. Klik **Edit**.
3. Perbarui data atau status akun.
4. Simpan perubahan.

Hindari menghapus akun yang sudah memiliki transaksi akademik atau keuangan. Gunakan penonaktifan bila riwayat pengguna harus dipertahankan.

### Import dan export

Gunakan **Export** untuk memperoleh data atau contoh struktur, lalu sesuaikan berkas sebelum **Import**. Jangan mengubah nama kolom template. Setelah impor, periksa jumlah data berhasil dan pesan kegagalan.

## 6.2 Mengelola struktur akademik

Lakukan pengisian dengan urutan berikut:

1. **Data Fakultas** — buat fakultas yang menjadi induk program studi.
2. **Data Program Studi** — hubungkan program studi ke fakultas.
3. **Data Tahun Akademik** — buat periode semester.
4. **Master Mata Kuliah** — simpan identitas baku mata kuliah dan jumlah SKS.
5. **Data Kurikulum** — tentukan kurikulum program studi.
6. **Data Kelas** — buat kelas untuk periode dan program studi.
7. **Penawaran Mata Kuliah** — tawarkan mata kuliah kepada kelas tertentu.
8. **Jadwal Mingguan/Pertemuan** — atur dosen, ruang, hari, jam, dan pertemuan.

## 6.3 Membuat dan mengaktifkan periode akademik

1. Buka **Data Akademik > Data Tahun Akademik**.
2. Isi nama periode, kode, jenis periode (ganjil, genap, atau pendek), tahun mulai/selesai, dan rentang tanggal.
3. Klik simpan. Periode baru dibuat sebagai **Draft**.
4. Lengkapi konfigurasi akademik dan keuangan untuk periode tersebut.
5. Setelah siap, klik **Aktifkan** pada periode.

Mengaktifkan periode baru akan menutup periode aktif sebelumnya. Hanya periode draft yang dapat diubah atau dihapus, dan periode yang sudah memiliki data akademik tidak dapat dihapus.

## 6.4 Dashboard Pembukaan Periode

Dashboard ini digunakan untuk menilai kesiapan sebelum portal periode dibuka kepada dosen dan mahasiswa.

1. Pilih periode pada header.
2. Buka **Dashboard Pembukaan Periode** dari tombol yang tersedia pada halaman Tahun Akademik, Penawaran, atau Keuangan Periode.
3. Tinjau progres dan setiap pemeriksaan: identitas periode, kurikulum/prodi, registrasi/kelas, penawaran/dosen, jadwal, dan tagihan.
4. Klik **Perbaiki data** pada pemeriksaan yang gagal atau memiliki peringatan.
5. Klik **Simpan pemeriksaan** untuk mencatat snapshot kesiapan.
6. Pastikan tidak ada kegagalan kritis.
7. Klik **Publikasikan** untuk membuka periode ke portal dosen dan mahasiswa.

Hanya Web Administrator yang dapat memublikasikan periode. Publikasi hendaknya dilakukan setelah Akademik dan Finance menyatakan data siap.

## 6.5 Menyalin konfigurasi periode

Fitur ini mengurangi pengisian ulang data yang sama dari periode sebelumnya.

1. Pada **Dashboard Pembukaan Periode**, pilih **Periode sumber**.
2. Pilih **Periode tujuan (draft)**.
3. Centang data yang akan disalin: kelas, penugasan dosen, penawaran, jadwal, dan/atau template tagihan.
4. Klik **Pratinjau salin**.
5. Periksa jumlah referensi dan konflik.
6. Jika sesuai, klik **Jalankan penyalinan**.
7. Periksa bagian **Riwayat Salin** dan lakukan koreksi pada data yang konflik.

Data transaksi seperti KRS, presensi, nilai, dan pembayaran tidak ikut disalin.

## 6.6 Mengelola penawaran mata kuliah

1. Pilih periode.
2. Buka halaman **Penawaran Periode** dari pengelolaan mata kuliah.
3. Pilih master mata kuliah, program studi, kurikulum, kelas, dosen utama, dosen pendamping jika ada, prasyarat, serta kapasitas.
4. Klik **Simpan penawaran**.
5. Atur **Jadwal Pengisian KRS** dengan waktu mulai dan selesai, lalu centang **Publikasikan** saat jendela KRS boleh digunakan.
6. Gunakan tombol **Peserta** untuk melihat mahasiswa yang mengambil penawaran.

Setelah penawaran dipakai dalam KRS, identitas utama penawaran dikunci. Dosen dan kapasitas masih dapat disesuaikan sesuai aturan aplikasi.

## 6.7 Jadwal mingguan dan generator pertemuan

1. Buka **Jadwal Mingguan** pada periode terpilih.
2. Pilih penawaran, kelas, dosen, ruang, hari, serta jam mulai dan selesai.
3. Simpan jadwal.
4. Jika ada bentrok, perbaiki dosen, ruang, hari, atau jam.
5. Pengecualian bentrok hanya digunakan oleh Web Administrator dengan alasan yang dapat diaudit.
6. Tambahkan **Kalender Hari Libur** agar generator tidak membuat pertemuan pada tanggal libur.
7. Klik **Generate** pada jadwal.
8. Isi tanggal mulai, tanggal selesai, dan jumlah pertemuan.
9. Klik **Pratinjau**, periksa tanggal dan duplikat, lalu **Konfirmasi dan buat pertemuan**.

Gunakan **Cetak Rekap Presensi** untuk memperoleh rekap pertemuan dan kehadiran pada periode.

## 6.8 Pengaturan website dan pemeliharaan

Menu **Web Settings** digunakan untuk mengubah identitas dan konfigurasi tampilan situs. Periksa kembali perubahan pada halaman publik setelah menyimpan.

Web Administrator juga dapat:

- membersihkan cache melalui ikon muat ulang di header;
- mengekspor basis data;
- mengimpor basis data;
- memeriksa atau menjalankan pembaruan aplikasi jika fitur tersedia.

> **Peringatan:** impor basis data dan pembaruan aplikasi merupakan tindakan berisiko tinggi. Buat cadangan, pastikan berkas benar, dan lakukan pada waktu pemeliharaan. Jangan menjalankannya hanya untuk mencoba fitur.

---

# 7. Panduan Departemen Akademik

## 7.1 Data mahasiswa

1. Buka **Menu Akademik > Data Mahasiswa**.
2. Gunakan pencarian untuk menemukan mahasiswa.
3. Klik **Tambah** untuk membuat data baru atau **Edit** untuk memperbarui data.
4. Pastikan NIM, program studi, kelas, kontak, dan status akun benar.

Gunakan import untuk data dalam jumlah besar dan selalu periksa hasilnya setelah proses selesai.

## 7.2 Registrasi mahasiswa per periode

Registrasi menghubungkan mahasiswa dengan periode, kelas, dosen wali, status akademik, dan batas SKS.

1. Pilih periode pada header.
2. Buka **Data Mahasiswa**, lalu edit mahasiswa.
3. Cari bagian registrasi/status akademik periode.
4. Isi kelas, dosen wali, status akademik, dan batas SKS sesuai kebijakan.
5. Simpan.

Mahasiswa yang belum memiliki registrasi pada periode aktif tidak dapat mengisi KRS atau menggunakan konteks akademik periode tersebut.

## 7.3 Status akademik mahasiswa

Pada halaman edit mahasiswa, status periode dapat diubah menjadi status yang disediakan aplikasi, misalnya aktif, cuti, nonaktif, lulus, atau status institusi lainnya.

1. Pastikan periode benar.
2. Pilih status.
3. Isi alasan/keterangan jika diminta.
4. Simpan.

Periode tertutup atau diarsipkan hanya dapat dilihat. Perubahan status harus dilakukan pada periode yang masih dapat ditulis.

## 7.4 Kenaikan semester/registrasi massal

1. Buka **Kenaikan Semester**.
2. Pilih periode dan kelas sumber.
3. Pilih periode dan kelas tujuan.
4. Pilih dosen wali tujuan.
5. Tentukan keputusan untuk setiap kategori status mahasiswa.
6. Klik **Tampilkan Pratinjau**.
7. Periksa jumlah mahasiswa, data yang akan dibuat, dilewati, atau konflik.
8. Jika benar, klik **Jalankan Proses**.
9. Periksa **Audit Proses Terakhir**.

Jangan menjalankan proses tanpa pratinjau. Koreksi kelas atau keputusan status sebelum eksekusi jika hasil pratinjau tidak sesuai.

## 7.5 Kurikulum, kelas, dan mata kuliah

- **Data Kurikulum:** buat kurikulum per program studi dan rentang berlakunya.
- **Data Kelas:** buat kelas pada periode terpilih, hubungkan dengan program studi/program kuliah, dan lihat atau cetak daftar mahasiswa.
- **Master Mata Kuliah:** identitas baku mata kuliah yang dapat dipakai lintas periode.
- **Data Mata Kuliah/Penawaran:** realisasi mata kuliah pada periode, kelas, kurikulum, dan dosen tertentu.

Gunakan master mata kuliah sebagai sumber utama agar nama dan SKS konsisten antarperiode.

## 7.6 Penawaran dan jendela KRS

Ikuti prosedur pada bagian **6.6 Mengelola penawaran mata kuliah**. Departemen Akademik dapat membuat, menyalin, mengubah, dan menghapus penawaran selama periode masih dapat dikelola.

Sebelum membuka KRS, pastikan:

- mahasiswa telah diregistrasikan;
- kelas dan batas SKS sudah benar;
- dosen wali telah ditetapkan;
- penawaran sesuai kelas dan program studi;
- prasyarat serta kapasitas sudah diperiksa;
- jendela KRS sudah dipublikasikan.

## 7.7 Jadwal kuliah dan presensi

Departemen Akademik dapat membuat jadwal kuliah, melihat presensi, memperbarui keterangan presensi, mencetak daftar hadir, serta mengelola jadwal mingguan dan pertemuan.

Untuk jadwal baru, prioritaskan alur **Penawaran > Jadwal Mingguan > Generate Pertemuan**. Periksa bentrok dosen, ruang, dan kelas sebelum menyimpan.

## 7.8 Nilai mahasiswa

1. Buka mata kuliah/penawaran yang dikelola.
2. Pilih halaman nilai.
3. Masukkan komponen nilai sesuai kolom yang tersedia.
4. Periksa identitas mahasiswa dan periode.
5. Simpan.

Nilai yang sudah dipublikasikan atau periode yang telah ditutup sebaiknya hanya dikoreksi melalui prosedur resmi institusi.

## 7.9 Pemeriksaan kesiapan periode

Departemen Akademik dapat membuka Dashboard Pembukaan Periode untuk melihat dan menyimpan pemeriksaan pada bagian akademik. Perbaiki semua pemeriksaan gagal, lalu informasikan Web Administrator bahwa periode siap dipublikasikan.

---

# 8. Panduan Departemen Finance

## 8.1 Template tagihan periode

1. Pilih periode pada header.
2. Buka **Data Tagihan**, lalu klik **Keuangan Periode**.
3. Isi nama, jenis, nominal, tanggal terbit, dan jatuh tempo.
4. Pilih satu jenis target:
   - mahasiswa tertentu;
   - program studi;
   - program kuliah;
   - kelompok status/semua mahasiswa.
5. Isi hanya kolom target yang sesuai.
6. Centang **Wajib lunas sebelum KRS** bila tagihan menjadi syarat administrasi KRS.
7. Klik **Simpan template**.

## 8.2 Pratinjau dan penerbitan tagihan

1. Pada daftar template, klik **Pratinjau**.
2. Periksa jumlah calon penerima dan total nominal.
3. Pastikan target serta nominal benar.
4. Klik **Terbitkan** dan konfirmasi.

Pratinjau tidak mengubah data. Penerbitan membuat tagihan nyata kepada seluruh penerima yang memenuhi target.

## 8.3 Override administrasi KRS

Override digunakan untuk memberi pengecualian administratif yang tercatat.

1. Pada halaman **Keuangan Periode**, cari **Override Administrasi KRS**.
2. Pilih mahasiswa.
3. Isi alasan minimal 10 karakter.
4. Isi tanggal berlaku sampai jika pengecualian bersifat sementara.
5. Klik **Catat**.

Gunakan hanya berdasarkan persetujuan pejabat yang berwenang. Identitas pelaksana dan alasan disimpan untuk audit.

## 8.4 Data tagihan dan pembayaran

- **Data Tagihan** digunakan untuk melihat, menambah, mengubah, atau membatalkan tagihan sesuai statusnya.
- **Data Pembayaran** digunakan untuk mencatat dan meninjau pembayaran.
- **Data Keuangan** digunakan untuk transaksi masuk/keluar dan ringkasan saldo sesuai fungsi aplikasi.

Selalu cocokkan kode tagihan, mahasiswa, nominal, periode, dan status sebelum menyimpan perubahan.

## 8.5 Laporan periode

Halaman Keuangan Periode menampilkan ringkasan tagihan dan tunggakan menurut kelompok yang tersedia. Gunakan laporan untuk rekonsiliasi dengan data pembayaran dan kesiapan pembukaan KRS.

## 8.6 Persetujuan absensi staf

1. Buka **Menu Administrasi > Data Approval > Approval Absensi**.
2. Periksa permohonan dan keterangannya.
3. Pilih **Terima** atau **Tolak**.
4. Gunakan tab/daftar disetujui dan ditolak untuk meninjau keputusan.

## 8.7 Pemeriksaan kesiapan periode

Departemen Finance dapat memeriksa komponen tagihan melalui Dashboard Pembukaan Periode. Pastikan template wajib, target, nominal, dan jadwal penerbitan sudah siap sebelum periode dipublikasikan.

---

# 9. Panduan Departemen Support, Officer, dan Admin

## 9.1 Departemen Support: gedung dan ruangan

### Data gedung

1. Buka **Master Inventaris > Data Gedung**.
2. Tambahkan nama dan informasi gedung.
3. Simpan.

### Data ruangan

1. Buka **Master Inventaris > Data Ruangan**.
2. Pilih gedung, isi nama ruang dan kapasitas.
3. Simpan.

Kapasitas ruang dipakai saat menyusun jadwal. Perbarui kapasitas jika terjadi perubahan fisik. Gunakan import/export untuk pengelolaan massal dengan mengikuti struktur template.

## 9.2 Departemen Officer

Departemen Officer menggunakan Dashboard, profil, notifikasi, berita, album foto, dan dokumen sesuai hak akses. Bila institusi memberi tugas tambahan, menu akan tampil berdasarkan konfigurasi akun.

## 9.3 Departemen Admin

Departemen Admin menggunakan fitur umum staf, dapat melihat pemeriksaan pembukaan periode sesuai kewenangannya, dan dapat menyetujui KRS yang telah diajukan. Buka **Persetujuan KRS**, cari mahasiswa, tinjau mata kuliah serta total SKS, lalu klik **Setujui KRS**. Untuk persetujuan massal, filter status **Diajukan**, centang KRS, pilih **Setujui dan kunci** pada **Aksi massal**, lalu konfirmasi. Alternatifnya, gunakan **Update KRS melalui Excel** dengan kolom `NIM`, `Nama`, dan `Aksi`; isi aksi `setujui`, unggah file, periksa pratinjau, lalu eksekusi. Persetujuan akan mengunci KRS, mengirim notifikasi, dan dicatat dalam audit. Penambahan, penghapusan, atau pembukaan kembali isi KRS tetap dilakukan oleh Departemen Academic atau Web Administrator.

---

# 10. Panduan Dosen

## 10.1 Masuk dan dashboard

Masuk melalui portal dosen menggunakan NIDN, nomor telepon, atau email. Dashboard menampilkan ringkasan serta pemberitahuan yang ditujukan kepada dosen.

## 10.2 Persetujuan KRS

1. Buka **Data Akademik > Persetujuan KRS**.
2. Pilih pengajuan mahasiswa bimbingan.
3. Periksa mata kuliah, SKS tiap mata kuliah, dan total SKS.
4. Tulis catatan keputusan bila diperlukan.
5. Klik **Setujui dan kunci** jika KRS sesuai, atau **Tolak** jika perlu diperbaiki.

KRS yang disetujui dikunci dan menjadi dasar jadwal mahasiswa. Jika ditolak, berikan catatan yang jelas agar mahasiswa dapat memperbaikinya.

## 10.3 Jadwal perkuliahan

1. Buka **Data Akademik > Jadwal Perkuliahan**.
2. Periksa mata kuliah, kelas, ruang, tanggal, dan jam.
3. Gunakan tombol presensi untuk melihat kehadiran mahasiswa.
4. Tambahkan atau perbarui keterangan presensi bila diperlukan.

Dosen hanya dapat mengakses jadwal yang diampu pada periode aktif.

## 10.4 Umpan balik perkuliahan

Dari jadwal, buka halaman **Feedback** untuk melihat penilaian mahasiswa terhadap perkuliahan. Gunakan informasi secara profesional dan jangan mencoba mengidentifikasi mahasiswa bila umpan balik ditujukan sebagai evaluasi.

## 10.5 Membuat tugas

1. Buka **Data Akademik > Kelola Tugas**.
2. Klik **Tambah**.
3. Pilih jadwal yang diampu.
4. Isi judul, rincian tugas, tanggal batas, dan jam batas.
5. Simpan.

Tugas hanya dapat dibuat untuk jadwal dosen pada periode aktif dan masih dapat ditulis.

## 10.6 Memeriksa pengumpulan dan memberi nilai

1. Buka tugas yang akan diperiksa.
2. Lihat daftar pengumpulan mahasiswa.
3. Buka detail jawaban dan berkas lampiran.
4. Masukkan skor 0–10.
5. Simpan.

Nilai tugas yang sudah disimpan tidak dapat diubah melalui alur biasa dan memerlukan prosedur koreksi khusus. Periksa skor sebelum menyimpan.

---

# 11. Panduan Mahasiswa

## 11.1 Dashboard dan profil

Dashboard menampilkan ringkasan tagihan, jadwal, presensi, dan pemberitahuan. Buka **My Profile** dari menu akun untuk melengkapi foto, identitas, kontak, alamat, data orang tua/wali, dan kata sandi.

Pastikan email serta nomor telepon selalu aktif karena digunakan untuk pemulihan akun dan komunikasi.

## 11.2 Mengisi KRS

Sebelum mengisi KRS, pastikan:

- periode telah dipublikasikan;
- mahasiswa sudah diregistrasikan pada periode tersebut;
- jadwal pengisian KRS sedang dibuka;
- kewajiban administrasi yang disyaratkan sudah dipenuhi atau memiliki override;
- status akademik mengizinkan pengisian KRS.

Langkah pengisian:

1. Buka **Menu Akademik > Kartu Rencana Studi**.
2. Periksa nama periode, kelas, status KRS, dan batas SKS.
3. Pada daftar penawaran, klik **Tambah** pada mata kuliah yang dipilih.
4. Periksa prasyarat, kapasitas, dosen, dan jumlah SKS.
5. Gunakan **Hapus** untuk membatalkan mata kuliah selama KRS masih dapat diedit.
6. Pastikan total SKS tidak melebihi batas.
7. Isi catatan untuk dosen wali bila perlu.
8. Klik **Ajukan KRS** dan konfirmasi.

Status KRS:

| Status | Arti |
|---|---|
| Draft | Masih dapat ditambah atau dikurangi |
| Submitted/Diajukan | Menunggu keputusan dosen wali |
| Approved/Disetujui | Disetujui dan dikunci |
| Rejected/Ditolak | Perlu diperbaiki sesuai catatan dosen wali |

Klik **Cetak KRS** untuk membuka versi cetak. Simpan atau cetak melalui fasilitas peramban.

## 11.3 Melihat jadwal kuliah

1. Buka **Data Jadwal Kuliah**.
2. Periksa mata kuliah, dosen, ruang/gedung, tanggal, dan jam.
3. Jadwal yang tampil mengikuti periode aktif dan KRS yang disetujui.

Jika jadwal kosong, periksa apakah KRS sudah disetujui dan periode sudah dipublikasikan.

## 11.4 Melakukan presensi kuliah

1. Pada hari dan jam perkuliahan, buka **Data Jadwal Kuliah**.
2. Pilih jadwal dan klik aksi presensi.
3. Pilih status **Hadir**, **Izin**, atau **Sakit** sesuai kondisi.
4. Unggah foto bukti berformat gambar, maksimal 8 MB.
5. Kirim presensi.

Presensi hanya dapat dilakukan selama jadwal berlangsung dan satu kali untuk setiap pertemuan. Mahasiswa harus terdaftar pada KRS yang disetujui untuk pertemuan terkait.

## 11.5 Memberikan feedback

1. Buka jadwal kuliah terkait.
2. Pilih tingkat kepuasan: **Tidak Puas**, **Cukup Puas**, atau **Sangat Puas**.
3. Isi alasan.
4. Kirim.

Feedback hanya dapat dikirim satu kali untuk satu jadwal.

## 11.6 Mengumpulkan tugas

1. Buka **Data Tugas Kuliah**.
2. Pilih tugas dan baca rincian serta batas waktu.
3. Isi deskripsi jawaban.
4. Unggah berkas utama pada **File 1**. Berkas tambahan dapat diunggah hingga File 8.
5. Format yang diterima: PDF, Word, Excel, PowerPoint, atau gambar.
6. Ukuran maksimal setiap berkas adalah 20 MB.
7. Klik simpan/kumpulkan.

Pengumpulan hanya dapat dilakukan sekali melalui alur biasa. Pastikan berkas dapat dibuka dan merupakan versi final sebelum dikirim.

## 11.7 Melihat nilai

1. Buka **Data Nilai**.
2. Pilih **Periode Aktif** untuk melihat nilai semester berjalan.
3. Pilih tampilan transkrip jika ingin melihat seluruh periode.
4. Periksa nilai mata kuliah, IPS, IPK, dan rekap nilai tugas yang tersedia.

Hubungi bagian Akademik jika terdapat ketidaksesuaian. Sertakan nama mata kuliah, periode, dan bukti pendukung tanpa mengirim kata sandi.

## 11.8 Tagihan dan pembayaran

1. Buka **Menu Finansial > Data Tagihan**.
2. Periksa daftar tagihan periode aktif dan riwayat pembayaran.
3. Pilih tagihan untuk melihat rincian.
4. Tambahkan catatan bila diperlukan, lalu lanjutkan pembayaran.
5. Selesaikan pembayaran pada halaman penyedia pembayaran.
6. Kembali ke aplikasi dan tunggu verifikasi status.
7. Setelah status lunas, klik **Invoice** untuk mengunduh bukti pembayaran PDF.

Jangan menutup halaman saat proses baru dimulai. Jika saldo sudah terpotong tetapi status belum lunas, jangan langsung membayar ulang; simpan bukti transaksi dan hubungi Finance melalui ticket support.

## 11.9 Ticket support

### Membuka ticket

1. Buka **Menu Bantuan > Ticket Support > Buka Ticket**.
2. Pilih departemen tujuan.
3. Isi subjek dan uraian masalah secara jelas.
4. Lampirkan bukti bila fasilitas tersedia.
5. Kirim.

### Melihat dan membalas ticket

1. Buka **Lihat Ticket**.
2. Pilih nomor ticket.
3. Baca jawaban petugas.
4. Tambahkan balasan jika masalah belum selesai.

Gunakan satu ticket untuk satu masalah dan jangan mengirim kata sandi, PIN, atau data kartu pembayaran.

---

# 12. Alur Operasional Satu Periode Akademik

Urutan berikut direkomendasikan agar data antarfitur konsisten.

1. **Web Administrator** membuat periode sebagai draft.
2. **Web Administrator/Akademik** memilih periode tujuan pada header.
3. Salin konfigurasi periode sebelumnya bila diperlukan.
4. **Akademik** memeriksa program studi, kurikulum, kelas, dan master mata kuliah.
5. **Akademik** meregistrasikan mahasiswa, menetapkan kelas, dosen wali, status, dan batas SKS.
6. **Akademik** membuat penawaran mata kuliah dan menetapkan dosen serta kapasitas.
7. **Support/Akademik** memastikan gedung dan ruang siap.
8. **Akademik** membuat jadwal mingguan, kalender libur, lalu menghasilkan pertemuan.
9. **Finance** membuat dan meninjau template tagihan periode.
10. **Web Administrator/Akademik/Finance** menyimpan pemeriksaan kesiapan masing-masing.
11. **Web Administrator** mengaktifkan dan memublikasikan periode setelah seluruh bagian siap.
12. **Finance** menerbitkan tagihan sesuai jadwal.
13. **Akademik** membuka jendela KRS.
14. **Mahasiswa** menyusun dan mengajukan KRS.
15. **Dosen wali** menyetujui atau menolak KRS.
16. Perkuliahan berjalan: jadwal, presensi, tugas, feedback, dan nilai dicatat.
17. **Finance** merekonsiliasi pembayaran dan tunggakan.
18. **Akademik** memeriksa nilai serta status akhir periode.
19. Lakukan kenaikan semester/registrasi periode berikutnya melalui pratinjau.
20. Periode lama ditutup ketika periode baru diaktifkan dan data historis menjadi hanya-baca sesuai aturan.

---

# 13. Pemecahan Masalah

| Masalah | Pemeriksaan dan tindakan |
|---|---|
| Tidak dapat masuk | Pastikan portal sesuai jenis akun, identitas benar, Caps Lock mati, dan akun aktif. Gunakan lupa kata sandi bila perlu. |
| Menu tidak muncul | Periksa peran dan status akun. Hubungi Web Administrator jika hak akses tidak sesuai. |
| Data tampil pada semester yang salah | Periksa pemilih periode di header, lalu pilih periode yang benar. |
| Periode hanya-baca | Periode sudah ditutup/diarsipkan. Lakukan perubahan pada periode aktif/draft atau ikuti prosedur koreksi resmi. |
| Mahasiswa tidak dapat mengisi KRS | Periksa publikasi periode, registrasi, status akademik, batas SKS, jadwal KRS, tagihan wajib, prasyarat, dan kapasitas. |
| Jadwal mahasiswa kosong | Pastikan KRS telah disetujui, penawaran memiliki jadwal, dan periode telah dipublikasikan. |
| Jadwal bentrok | Ubah dosen, kelas, ruang, hari, atau jam. Pengecualian hanya untuk kondisi yang sah dan wajib disertai alasan. |
| Presensi tidak dapat dibuka | Presensi hanya tersedia pada tanggal dan jam jadwal serta hanya satu kali. Pastikan KRS disetujui. |
| Unggah berkas gagal | Periksa format dan ukuran, gunakan nama berkas sederhana, stabilkan koneksi, lalu coba kembali. |
| Tugas sudah dianggap terkumpul | Pengumpulan hanya sekali. Hubungi dosen bila berkas salah dan diperlukan prosedur koreksi. |
| Pembayaran belum terverifikasi | Jangan membayar ulang. Simpan bukti, muat ulang setelah beberapa saat, lalu hubungi Finance melalui ticket. |
| Email reset tidak diterima | Periksa spam dan pastikan email akun benar. Minta administrator memperbarui email bila perlu. |
| Halaman menampilkan 403 | Akun tidak berwenang membuka fitur tersebut. Gunakan akun/peran yang benar. |
| Halaman menampilkan 404 | Data tidak ditemukan, bukan milik pengguna, berada di luar periode aktif, atau tautan sudah tidak berlaku. |

Saat melaporkan masalah, sertakan waktu kejadian, menu yang dibuka, periode, nomor/kode data, pesan kesalahan, dan tangkapan layar. Jangan sertakan kata sandi atau rahasia pembayaran.

---

# 14. Keamanan dan Praktik Penggunaan yang Baik

1. Gunakan akun sendiri dan jangan berbagi kata sandi.
2. Gunakan kata sandi unik dan ubah jika diduga bocor.
3. Jangan mengaktifkan **Ingat saya** pada perangkat umum.
4. Selalu logout setelah selesai.
5. Periksa periode, identitas, nominal, dan sasaran sebelum menyimpan.
6. Gunakan pratinjau sebelum proses massal atau penerbitan transaksi.
7. Simpan dokumen pribadi hanya pada fitur yang memang memerlukannya.
8. Jangan mengunggah berkas berbahaya atau konten yang melanggar kebijakan institusi.
9. Jangan membagikan kunci pembayaran, kredensial email, cadangan basis data, atau berkas konfigurasi.
10. Untuk impor basis data atau pembaruan sistem, buat cadangan dan gunakan prosedur pemeliharaan resmi.

---

# 15. Glosarium

| Istilah | Arti |
|---|---|
| SIAKAD | Sistem Informasi Akademik |
| Periode akademik | Rentang kegiatan akademik, misalnya semester ganjil, genap, atau pendek |
| Draft | Data masih disiapkan dan dapat diubah |
| Aktif | Periode yang sedang menjadi konteks operasional staf |
| Dipublikasikan | Periode sudah dapat digunakan pada portal dosen dan mahasiswa |
| Ditutup/diarsipkan | Periode historis yang umumnya hanya dapat dibaca |
| Registrasi mahasiswa | Penempatan mahasiswa pada periode, kelas, dosen wali, status, dan batas SKS |
| KRS | Kartu Rencana Studi |
| Penawaran mata kuliah | Mata kuliah yang dibuka untuk kelas/program studi pada periode tertentu |
| SKS | Satuan Kredit Semester |
| Dosen wali | Dosen yang membimbing dan mengambil keputusan atas KRS mahasiswa |
| Jadwal mingguan | Pola hari, jam, dosen, kelas, dan ruang untuk suatu penawaran |
| Pertemuan kuliah | Kejadian kuliah pada tanggal tertentu yang dihasilkan dari jadwal mingguan |
| IPS | Indeks Prestasi Semester |
| IPK | Indeks Prestasi Kumulatif |
| Ticket support | Percakapan bantuan antara mahasiswa dan departemen terkait |
| Override | Pengecualian yang diberikan secara resmi dan dicatat untuk audit |

---

# 16. Checklist Operasional

## 16.1 Sebelum periode dipublikasikan

- [ ] Identitas dan rentang tanggal periode lengkap.
- [ ] Program studi dan kurikulum benar.
- [ ] Kelas periode sudah dibuat.
- [ ] Mahasiswa sudah diregistrasikan dan memiliki status serta batas SKS.
- [ ] Dosen wali sudah ditetapkan.
- [ ] Penawaran mata kuliah, dosen, prasyarat, dan kapasitas sudah benar.
- [ ] Ruang dan kapasitas tersedia.
- [ ] Jadwal bebas bentrok.
- [ ] Kalender libur dan pertemuan sudah dibuat.
- [ ] Template tagihan wajib sudah diperiksa.
- [ ] Snapshot kesiapan tidak memiliki kegagalan kritis.
- [ ] Persetujuan Akademik dan Finance telah diperoleh.

## 16.2 Sebelum KRS dibuka

- [ ] Periode sudah aktif dan dipublikasikan.
- [ ] Waktu mulai dan selesai KRS benar.
- [ ] Jendela KRS sudah dipublikasikan.
- [ ] Target tagihan wajib sudah benar.
- [ ] Mahasiswa dapat melihat penawaran sesuai kelas.
- [ ] Dosen wali dapat melihat pengajuan bimbingannya.

## 16.3 Sebelum menutup periode

- [ ] Presensi dan tugas telah selesai direkap.
- [ ] Nilai telah diperiksa.
- [ ] Koreksi resmi telah diselesaikan.
- [ ] Pembayaran dan tunggakan telah direkonsiliasi.
- [ ] Data penting telah diekspor/dicadangkan sesuai kebijakan.
- [ ] Pratinjau kenaikan semester telah diperiksa.
- [ ] Periode berikutnya sudah siap sebelum diaktifkan.

---

**Akhir dokumen**
