# Panduan Departement Support

Departement Support mengelola data gedung dan ruangan yang dipakai dalam penjadwalan, serta fitur publikasi umum staf.

## Masuk dan pemeriksaan awal

1. Buka `[alamat-aplikasi]/admin/auth-signin` dan masuk dengan akun staf.
2. Pastikan peran yang tampil adalah **Departement Support**.
3. Periksa notifikasi serta periode akademik pada header.
4. Pilih periode yang akan diperiksa sebelum menindaklanjuti masalah jadwal.

## Mengelola data gedung

1. Buka **Support Departement > Master Inventaris > Data Gedung**.
2. Cari gedung terlebih dahulu untuk mencegah duplikasi.
3. Klik tambah, isi kode/nama dan informasi lain yang diminta, lalu simpan.
4. Gunakan edit jika nama atau keterangan berubah.
5. Hapus hanya jika gedung belum dipakai data ruangan atau jadwal.

Gunakan penamaan konsisten, misalnya nama resmi gedung, bukan singkatan yang berbeda-beda.

## Mengelola data ruangan

1. Buka **Master Inventaris > Data Ruangan**.
2. Cari ruangan berdasarkan nama dan gedung.
3. Klik tambah dan pilih gedung induk.
4. Isi nama/kode ruang dan kapasitas aktual.
5. Simpan, lalu pastikan ruang muncul pada daftar.

Kapasitas ruang memengaruhi penyusunan jadwal. Perbarui data ketika ruang direnovasi, dipindahkan, atau tidak dapat digunakan.

## Import dan export inventaris

1. Gunakan **Export** untuk memperoleh data atau struktur contoh.
2. Simpan salinan asli sebelum mengubah spreadsheet.
3. Jangan mengubah nama dan urutan kolom wajib.
4. Bersihkan baris kosong dan periksa kode yang duplikat.
5. Jalankan **Import**, lalu periksa jumlah berhasil/gagal.
6. Lakukan pemeriksaan sampel pada gedung dan ruangan hasil impor.

## Mendukung penyusunan jadwal

1. Minta daftar kebutuhan ruang dari Academic.
2. Pastikan gedung, ruang, dan kapasitas sudah tercatat.
3. Sampaikan ruang yang tidak aktif atau sedang dalam perbaikan.
4. Jika terjadi bentrok, Support memperbaiki data inventaris; perubahan jadwal dilakukan oleh Academic/Web Administrator.

## Fitur publikasi umum

Support juga dapat mengelola **Data Pemberitahuan**, **Data Berita**, **Data Album Foto**, dan **Data Document**. Pastikan materi telah disetujui, sasaran benar, dan tidak memuat data sensitif.

## Checklist data ruang

- [ ] Tidak ada kode/nama ruang ganda pada gedung yang sama.
- [ ] Setiap ruang terhubung ke gedung yang benar.
- [ ] Kapasitas sesuai kondisi terkini.
- [ ] Ruang yang tidak dapat digunakan telah diinformasikan ke Academic.
- [ ] Hasil import telah diperiksa.

## Kendala umum

| Kendala | Tindakan |
|---|---|
| Gedung tidak dapat dihapus | Periksa apakah masih memiliki ruangan atau dipakai jadwal. |
| Ruang tidak muncul saat penjadwalan | Pastikan data tersimpan dan informasikan kode ruang kepada Academic. |
| Import sebagian gagal | Periksa header, kode duplikat, kolom wajib, dan format data. |
| Diminta mengubah jadwal | Koordinasikan kepada Academic/Web Administrator. |

[Kembali ke daftar panduan](README.md)
