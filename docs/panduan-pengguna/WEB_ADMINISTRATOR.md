# Panduan Web Administrator

Web Administrator memiliki akses terluas: pengguna, struktur akademik, pembukaan periode, inventaris, keuangan, publikasi, pengaturan situs, dan pemeliharaan. Gunakan akun ini hanya untuk pekerjaan yang memang memerlukan kewenangan tinggi.

## Masuk dan pemeriksaan awal

1. Buka `[alamat-aplikasi]/admin/auth-signin`.
2. Masuk dengan akun Web Administrator.
3. Periksa identitas akun, notifikasi, dan periode akademik pada header.
4. Pastikan terdapat cadangan dan jadwal pemeliharaan sebelum tindakan sistem berisiko.

## Memilih konteks periode

1. Klik pemilih periode pada header.
2. Pilih periode yang akan dikerjakan.
3. Tunggu pemuatan ulang dan cocokkan kode periode pada halaman.
4. Ingat bahwa label **Aktif** berbeda dari periode draft yang sedang disiapkan.

Periode tertutup/arsip hanya-baca. Perubahan data historis harus mengikuti prosedur koreksi resmi.

## Mengelola akun pengguna

Menu **Data Pengguna** mencakup Data Admin, Pegawai, Dosen, dan Mahasiswa.

### Menambah atau mengubah akun

1. Pilih kelompok pengguna yang benar.
2. Cari identitas terlebih dahulu untuk menghindari duplikasi.
3. Klik tambah atau edit.
4. Isi identitas, nomor induk, kontak, peran, dan status akun.
5. Untuk staf, pilih departemen/hak akses yang tepat.
6. Simpan dan uji bahwa akun masuk melalui portal yang sesuai.

### Menonaktifkan akun

1. Buka data pengguna.
2. Ubah status menjadi tidak aktif.
3. Simpan dan catat alasan sesuai kebijakan.

Utamakan penonaktifan daripada penghapusan bila akun sudah memiliki transaksi, KRS, jadwal, nilai, tagihan, atau audit.

### Import dan export

1. Export data/template terlebih dahulu.
2. Simpan salinan asli.
3. Jangan mengubah nama kolom wajib.
4. Periksa identitas unik, peran, email, dan data referensi.
5. Import lalu tinjau jumlah berhasil/gagal.
6. Uji beberapa akun hasil impor tanpa membagikan kata sandi lewat kanal tidak aman.

## Menyiapkan struktur akademik

Urutan referensi yang dianjurkan:

1. **Data Fakultas**;
2. **Data Program Studi**;
3. **Data Tahun Akademik**;
4. **Program Kuliah**;
5. **Master Mata Kuliah**;
6. **Data Kurikulum**;
7. **Data Kelas**;
8. **Penawaran Mata Kuliah**;
9. **Jadwal Mingguan dan Pertemuan**.

Pastikan kode unik, hubungan induk benar, dan data belum digunakan sebelum menghapus referensi.

## Membuat periode akademik

1. Buka **Data Akademik > Data Tahun Akademik**.
2. Klik tambah.
3. Isi kode/nama, jenis semester, tahun mulai/selesai, serta rentang tanggal.
4. Simpan sebagai **Draft**.
5. Pilih draft tersebut pada header dan lengkapi akademik serta finance.
6. Aktifkan hanya setelah konsekuensi penutupan periode lama dipahami.

Mengaktifkan periode baru menutup periode aktif sebelumnya. Periode yang sudah memiliki data tidak boleh dihapus.

## Wizard dan Dashboard Pembukaan Periode

1. Pilih **Wizard Periode Baru** atau buka Dashboard Pembukaan Periode.
2. Pilih periode draft yang dituju.
3. Tinjau identitas periode, kurikulum/prodi, registrasi/kelas, penawaran/dosen, jadwal, serta tagihan.
4. Gunakan tautan **Perbaiki data** untuk setiap kegagalan.
5. Minta Academic dan Finance menyelesaikan pemeriksaan bagiannya.
6. Jalankan dan simpan snapshot pemeriksaan terbaru.
7. Pastikan tidak ada kegagalan kritis.
8. Klik **Publikasikan** hanya setelah persetujuan operasional diperoleh.

Publikasi membuat periode tersedia di portal dosen dan mahasiswa; ini berbeda dari sekadar membuat draft.

## Menyalin konfigurasi periode

1. Pilih periode sumber dan periode tujuan berstatus draft.
2. Pilih komponen: kelas, penugasan dosen, penawaran, jadwal, dan/atau template tagihan.
3. Klik **Pratinjau salin**.
4. Periksa referensi yang hilang, konflik, dan jumlah yang akan dibuat.
5. Jalankan penyalinan satu kali.
6. Buka riwayat proses dan perbaiki konflik.
7. Verifikasi kelas, dosen, kapasitas, ruang, waktu, nominal, dan tanggal pada periode tujuan.

Transaksi KRS, presensi, nilai, pembayaran, dan audit tidak ikut disalin.

## Mengelola registrasi dan KRS

1. Pastikan mahasiswa memiliki registrasi periode, kelas, dosen wali, status, dan batas SKS.
2. Buka **Kelola KRS Mahasiswa** untuk koreksi administratif.
3. Cari mahasiswa pada periode yang benar.
4. Tambah/hapus mata kuliah hanya berdasarkan dasar resmi.
5. Gunakan **Buka kembali** untuk KRS terkunci yang memang memerlukan perbaikan.
6. Untuk KRS berstatus **Submitted/Diajukan**, tinjau mata kuliah dan total SKS lalu gunakan **Setujui KRS** bila administrator perlu mengambil keputusan.
7. Dokumentasikan alasan dan minta mahasiswa/dosen menyelesaikan alur persetujuan kembali bila KRS dibuka ulang.

Persetujuan oleh Web Administrator mengunci KRS serta dicatat dalam audit. Untuk operasional rutin, utamakan persetujuan dosen wali atau Departement Admin sesuai kebijakan institusi.

### Aksi KRS massal

1. Gunakan pencarian dan filter status untuk mempersempit daftar.
2. Centang KRS atau pilih seluruh KRS yang memenuhi syarat pada halaman.
3. Pilih **Setujui dan kunci** untuk KRS diajukan, atau **Buka kembali** untuk KRS diajukan/disetujui/dikunci.
4. Isi catatan; alasan minimal 10 karakter wajib untuk membuka kembali.
5. Konfirmasikan aksi.

Proses bersifat atomik dan dibatasi maksimal 100 KRS: kegagalan satu pilihan membatalkan semua perubahan. Setiap KRS tetap menghasilkan audit dan notifikasi sendiri.

### Update KRS melalui Excel

1. Gunakan pencarian/filter pada **Pilih mahasiswa** dan klik **Unduh daftar Excel** agar file mengikuti daftar terfilter, atau gunakan **Unduh template** untuk seluruh mahasiswa periode.
2. Pertahankan kolom `NIM`, `Nama`, dan `Aksi`.
3. Isi `setujui` untuk menyetujui KRS diajukan atau `buka_kembali` untuk membuka KRS diajukan/disetujui/dikunci.
4. Biarkan aksi kosong untuk mahasiswa yang tidak diproses, lalu unggah file XLSX/CSV maksimal 2 MB dan 300 baris beraksi.
5. Periksa pratinjau; sistem mencocokkan nama dengan NIM dan memvalidasi status serta kewenangan.
6. Isi alasan minimal 10 karakter jika terdapat aksi `buka_kembali`.
7. Eksekusi dalam waktu 15 menit.

File sementara dihapus setelah dibaca. Seluruh baris divalidasi ulang saat eksekusi dan diproses secara atomik, dengan audit dan notifikasi per KRS.

Keterangan nilai aksi juga disertakan di file pada kolom **Keterangan Aksi**: `setujui` mengunci KRS diajukan, `buka_kembali` membuka KRS diajukan/disetujui/dikunci, dan nilai kosong mengabaikan baris.

## Penawaran mata kuliah dan KRS

1. Pilih periode.
2. Buat penawaran dari master mata kuliah.
3. Isi prodi, kurikulum, kelas, dosen utama/pendamping, prasyarat, kapasitas, dan data lain yang diminta.
4. Simpan dan periksa duplikasi.
5. Atur waktu mulai/selesai pengisian KRS.
6. Publikasikan jendela KRS setelah registrasi, finance, dan jadwal siap.
7. Gunakan halaman **Peserta** untuk memeriksa pengambil mata kuliah.

Identitas utama penawaran dikunci setelah dipakai dalam KRS. Lakukan koreksi sebelum mahasiswa mulai memilih.

## Jadwal, konflik, dan pertemuan

1. Buka **Jadwal Mingguan**.
2. Pilih penawaran, dosen, kelas, ruang, hari, serta jam.
3. Jika konflik terdeteksi, ubah sumber konflik.
4. Gunakan pengecualian hanya untuk kondisi yang benar-benar sah dan isi alasan audit.
5. Masukkan hari libur sebelum menghasilkan pertemuan.
6. Isi rentang tanggal dan target jumlah pertemuan.
7. Gunakan pratinjau untuk memeriksa libur, tanggal, dan duplikat.
8. Konfirmasi generate dan periksa hasilnya.
9. Gunakan rekap presensi untuk pemeriksaan periode.

## Inventaris

1. Kelola **Data Gedung** sebelum **Data Ruangan**.
2. Pastikan kapasitas ruangan sesuai kondisi aktual.
3. Jangan menghapus ruangan yang digunakan jadwal.
4. Koordinasikan perubahan inventaris dengan Support dan Academic.

## Keuangan

Web Administrator dapat melihat/mengelola tagihan, pembayaran, billing period, transaksi keuangan, override administrasi KRS, dan approval sesuai route yang tersedia. Untuk pekerjaan rutin, utamakan akun Finance agar pemisahan tugas dan audit tetap jelas.

Sebelum menerbitkan tagihan, wajib memeriksa periode, target, jumlah penerima, nominal, tanggal, dan syarat KRS melalui pratinjau.

## Publikasi dan pengaturan situs

- **Data Pemberitahuan:** periksa sasaran penerima.
- **Data Berita/Kategori:** periksa isi dan halaman publik.
- **Data Album Foto:** pastikan izin dan kelayakan foto.
- **Data Document:** unggah hanya versi final tanpa data rahasia.
- **Web Settings:** ubah identitas/tampilan situs lalu uji halaman publik dan portal.

## Cache, cadangan, import basis data, dan pembaruan

### Membersihkan cache

Gunakan fungsi bersihkan cache setelah perubahan konfigurasi bila tampilan masih memakai data lama. Tindakan ini tidak menggantikan pengujian.

### Export basis data

1. Jalankan export dari menu sistem.
2. Simpan di lokasi aman dengan akses terbatas.
3. Verifikasi ukuran dan kebijakan retensi.

### Import basis data

1. Jadwalkan waktu pemeliharaan dan hentikan transaksi pengguna.
2. Buat cadangan kondisi terakhir.
3. Verifikasi asal dan versi berkas impor.
4. Jalankan hanya setelah persetujuan.
5. Uji login, periode, KRS, jadwal, tagihan, dan pembayaran setelah pemulihan.

### Pembaruan aplikasi

1. Periksa versi dan catatan perubahan.
2. Pastikan cadangan serta rencana pemulihan tersedia.
3. Gunakan lingkungan uji jika tersedia.
4. Jalankan pada waktu pemeliharaan.
5. Lakukan smoke test seluruh portal.

Jangan menjalankan import basis data atau pembaruan hanya untuk mencoba fitur.

## Checklist publikasi periode

- [ ] Identitas dan tanggal periode lengkap.
- [ ] Kurikulum, kelas, registrasi, status, dosen wali, dan batas SKS benar.
- [ ] Penawaran, dosen, prasyarat, dan kapasitas benar.
- [ ] Gedung/ruang serta jadwal bebas konflik.
- [ ] Kalender libur dan pertemuan sudah dibuat.
- [ ] Template tagihan dan syarat KRS disetujui Finance.
- [ ] Snapshot kesiapan terbaru tidak memiliki kegagalan kritis.
- [ ] Academic dan Finance menyatakan siap.
- [ ] Cadangan serta rencana koreksi tersedia.

## Kendala umum

| Kendala | Pemeriksaan/tindakan |
|---|---|
| Menu pengguna tidak sesuai | Periksa `raw_type`/peran dan status aktif tanpa mengubah histori sembarangan. |
| Data ada pada periode salah | Hentikan perubahan, identifikasi data terdampak, lalu gunakan koreksi resmi. |
| Publikasi periode ditolak | Jalankan pemeriksaan dan perbaiki kegagalan kritis. |
| Jadwal bentrok | Koreksi dosen, kelas, ruang, hari, atau jam; audit pengecualian. |
| Import gagal sebagian | Periksa template, referensi, identitas unik, dan log/ringkasan kegagalan. |
| Setelah pembaruan terjadi gangguan | Hentikan perubahan lanjutan, kumpulkan bukti, dan jalankan rencana pemulihan. |

[Kembali ke daftar panduan](README.md)
