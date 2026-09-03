# Panduan Departement Admin

Departement Admin menangani administrasi umum dan publikasi. Fitur akademik, finance, inventaris, dan sistem hanya dikerjakan oleh departemen pemilik atau Web Administrator.

## Masuk dan pemeriksaan awal

1. Buka `[alamat-aplikasi]/admin/auth-signin`.
2. Masuk dengan username, telepon, atau email serta kata sandi.
3. Pastikan nama dan peran pada header benar.
4. Periksa notifikasi dan periode pada header sebelum bekerja.
5. Jika akun berstatus tidak aktif, hubungi Web Administrator.

## Dashboard dan profil

- **Home** menampilkan ringkasan data serta pemberitahuan.
- **My Profile** dibuka dari menu akun di kanan atas untuk memperbarui foto, data pribadi, kontak, dan kata sandi.
- Saat mengganti kata sandi, masukkan kata sandi lama dan gunakan kata sandi baru yang unik.

## Memeriksa kesiapan periode

Departement Admin dapat melihat pemeriksaan pembukaan periode sesuai kewenangan.

1. Pilih periode pada header.
2. Buka **Dashboard Pembukaan Periode** jika tautannya tersedia.
3. Tinjau pemeriksaan identitas periode, akademik, jadwal, dan tagihan.
4. Catat pemeriksaan yang gagal atau memperlihatkan peringatan.
5. Koordinasikan perbaikannya kepada Academic, Finance, Support, atau Web Administrator.

Departement Admin tidak memublikasikan periode dan tidak mengubah data khusus unit lain.

## Menyetujui KRS mahasiswa

1. Pilih periode akademik pada header.
2. Buka menu **Persetujuan KRS**.
3. Cari mahasiswa berdasarkan nama atau NIM, lalu klik **Tinjau**.
4. Pastikan status KRS adalah **Submitted/Diajukan**.
5. Periksa mahasiswa, kelas, dosen wali, mata kuliah, SKS setiap mata kuliah, dan total SKS.
6. Isi catatan persetujuan bila diperlukan.
7. Klik **Setujui KRS**, lalu konfirmasikan tindakan.

Persetujuan administrator mengunci KRS, memberi notifikasi kepada mahasiswa dan dosen wali, serta mencatat pelaksana dalam audit. Departement Admin tidak dapat menambah, menghapus, atau membuka kembali isi KRS. KRS berstatus draft, ditolak, atau sudah disetujui tidak dapat disetujui melalui tombol ini.

### Menyetujui beberapa KRS sekaligus

1. Gunakan filter status **Diajukan**.
2. Centang KRS yang akan diproses atau gunakan checkbox pada kepala tabel untuk memilih seluruh KRS yang memenuhi syarat pada halaman tersebut.
3. Pada bagian **Aksi massal**, pilih **Setujui dan kunci**.
4. Isi catatan bila diperlukan.
5. Klik **Jalankan**, lalu konfirmasikan tindakan.

Seluruh pilihan diproses sebagai satu transaksi. Jika salah satu KRS bukan milik periode terpilih atau tidak lagi berstatus diajukan, tidak ada KRS yang diubah. Maksimal 100 KRS dapat dikirim dalam satu proses.

### Menyetujui KRS melalui Excel

1. Pilih periode akademik yang benar.
2. Gunakan pencarian/filter status pada **Pilih mahasiswa**, lalu klik **Unduh daftar Excel** agar file hanya berisi mahasiswa pada hasil filter. Gunakan **Unduh template** bila memerlukan seluruh daftar periode.
3. Pertahankan kolom `NIM`, `Nama`, dan `Aksi`.
4. Isi `setujui` pada kolom `Aksi` untuk KRS yang akan disetujui.
5. Biarkan aksi kosong untuk mahasiswa yang tidak diproses. Simpan sebagai XLSX atau CSV, maksimal 2 MB dan 300 baris beraksi.
6. Unggah file lalu klik **Validasi dan pratinjau**.
7. Periksa NIM, nama, status sekarang, dan aksi pada pratinjau.
8. Isi catatan bila diperlukan, kemudian klik **Jalankan update Excel**.

Pratinjau berlaku 15 menit. Nama harus cocok dengan NIM, mahasiswa harus terdaftar pada periode terpilih, dan KRS harus berstatus diajukan. Seluruh file dibatalkan jika satu baris tidak valid atau berubah sebelum eksekusi.

Nilai `setujui` berarti menyetujui dan mengunci KRS berstatus diajukan. Nilai kosong berarti baris diabaikan. File unduhan menyertakan kolom **Keterangan Aksi** sebagai pengingat dan kolom tersebut tidak perlu diubah.

## Mengelola pemberitahuan

1. Buka **Menu Publikasi > Data Pemberitahuan**.
2. Tambahkan pemberitahuan baru.
3. Isi judul, isi, sasaran penerima, dan tautan bila diperlukan.
4. Periksa ejaan, tanggal, dan sasaran, lalu simpan.
5. Gunakan edit untuk koreksi; hapus hanya jika pemberitahuan memang tidak lagi diperlukan.

## Mengelola kategori dan berita

1. Buat kategori melalui **Data Berita > Kategori Berita** jika belum tersedia.
2. Buka **Data Berita > Berita**, lalu pilih tambah.
3. Isi judul, kategori, gambar, dan isi berita.
4. Hindari memuat data pribadi mahasiswa atau staf tanpa dasar yang sah.
5. Simpan dan periksa hasil publikasi melalui halaman lihat/situs publik.

## Mengelola album dan dokumen

### Album foto

1. Buka **Data Album Foto** dan pilih buat album.
2. Isi judul serta keterangan, lalu unggah foto yang telah disetujui.
3. Simpan dan periksa susunan album.

### Dokumen

1. Buka **Data Document** dan pilih tambah.
2. Gunakan nama yang menjelaskan isi dan versi dokumen.
3. Unggah berkas final, simpan, lalu uji unduhan dari halaman publik.

## Checklist sebelum logout

- [ ] Periode yang digunakan sudah benar.
- [ ] Sasaran pemberitahuan telah diperiksa.
- [ ] KRS yang disetujui sudah diperiksa mata kuliah dan total SKS-nya.
- [ ] Berita/album/dokumen tampil dengan benar.
- [ ] Tidak ada data rahasia dalam publikasi.
- [ ] Akun sudah logout pada perangkat bersama.

## Kendala umum

| Kendala | Tindakan |
|---|---|
| Menu unit lain tidak muncul | Ini sesuai pembagian akses; hubungi unit pemilik. |
| Publikasi tidak tampil | Pastikan penyimpanan berhasil dan periksa halaman publik/cache peramban. |
| Periode hanya-baca | Pilih periode aktif/draft atau ikuti prosedur koreksi. |
| 403 | Akun tidak memiliki kewenangan pada halaman tersebut. |

[Kembali ke daftar panduan](README.md)
