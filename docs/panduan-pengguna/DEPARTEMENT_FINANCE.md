# Panduan Departement Finance

Departement Finance mengelola template dan penerbitan tagihan, verifikasi pembayaran, transaksi keuangan, override administrasi KRS, serta approval absensi staf.

## Masuk dan memilih periode

1. Buka `[alamat-aplikasi]/admin/auth-signin`.
2. Masuk menggunakan akun **Departement Finance**.
3. Pilih periode akademik pada header.
4. Cocokkan periode, mahasiswa, kode tagihan, nominal, dan status sebelum setiap perubahan.

Kesalahan periode dapat menerbitkan tagihan kepada kelompok yang salah. Selalu gunakan pratinjau sebelum tindakan massal.

## Membuat template tagihan periode

1. Buka **Data Keuangan > Data Tagihan**, lalu **Keuangan Periode/Billing Period**.
2. Klik tambah template.
3. Isi nama, jenis tagihan, nominal, tanggal terbit, dan jatuh tempo.
4. Pilih tepat satu sasaran: mahasiswa tertentu, program studi, program kuliah, atau kelompok status/semua mahasiswa.
5. Centang **Wajib lunas sebelum KRS** hanya jika kebijakan mensyaratkannya.
6. Simpan template dan periksa kembali ringkasannya.

Template belum menjadi tagihan mahasiswa sampai diterbitkan.

## Pratinjau dan menerbitkan tagihan

1. Pada template, klik **Pratinjau**.
2. Periksa periode, kriteria target, jumlah penerima, nominal per penerima, dan total.
3. Batalkan dan perbaiki template jika terdapat penerima yang tidak semestinya.
4. Setelah benar, klik **Terbitkan/Issue** dan konfirmasi.
5. Periksa beberapa tagihan hasil penerbitan sebagai sampel.

Penerbitan adalah tindakan massal. Jangan menekan tombol berulang jika halaman lambat; periksa daftar tagihan lebih dahulu.

## Override administrasi KRS

1. Buka bagian **Override Administrasi KRS**.
2. Cari registrasi mahasiswa pada periode yang benar.
3. Isi alasan yang spesifik, minimal 10 karakter.
4. Isi masa berlaku jika pengecualian sementara.
5. Simpan dan catat referensi persetujuan internal.

Override tidak menghapus tagihan dan bukan bukti pelunasan. Fitur ini hanya melewati syarat administratif KRS sesuai kewenangan.

## Mengelola data tagihan

1. Buka **Data Keuangan > Data Tagihan**.
2. Gunakan pencarian berdasarkan mahasiswa/kode/status.
3. Buka rincian sebelum mengubah nominal, tanggal, atau status.
4. Jangan menghapus tagihan yang sudah memiliki pembayaran; gunakan prosedur pembatalan/koreksi institusi.
5. Simpan perubahan dan periksa audit/riwayat yang tersedia.

## Memverifikasi pembayaran manual

1. Buka **Data Keuangan > Data Pembayaran**.
2. Pilih pembayaran berstatus menunggu verifikasi.
3. Buka **Bukti** dan cocokkan pemilik, tanggal, nominal, rekening/kanal, dan kode tagihan.
4. Pilih keputusan **Terima** jika sah atau **Tolak** jika tidak memenuhi ketentuan.
5. Berikan alasan yang jelas saat menolak.
6. Periksa bahwa status tagihan berubah sesuai keputusan.

Jangan menyetujui bukti yang buram, nominal tidak sesuai, atau berpotensi duplikat tanpa klarifikasi.

## Data keuangan dan rekonsiliasi

1. Buka **Data Keuangan** untuk melihat/mencatat transaksi masuk dan keluar sesuai kebijakan.
2. Isi tanggal, kategori, nominal, serta keterangan yang dapat diaudit.
3. Cocokkan ringkasan aplikasi dengan laporan kanal pembayaran dan catatan institusi.
4. Selidiki transaksi duplikat, tertunda, atau selisih sebelum menutup laporan.

## Approval absensi staf

1. Buka **Menu Administrasi > Data Approval > Approval Absensi**.
2. Periksa pemohon, waktu, jenis permohonan, dan keterangan.
3. Pilih terima atau tolak berdasarkan ketentuan.
4. Tinjau daftar disetujui/ditolak untuk memastikan keputusan tercatat.

## Kesiapan periode

- [ ] Periode pada header benar.
- [ ] Template wajib sudah lengkap dan tidak duplikat.
- [ ] Sasaran dan nominal telah melalui pratinjau.
- [ ] Tanggal terbit/jatuh tempo sesuai kalender.
- [ ] Kebijakan wajib lunas sebelum KRS sudah benar.
- [ ] Override lama telah ditinjau masa berlakunya.
- [ ] Pemeriksaan finance pada Dashboard Pembukaan Periode tidak gagal.

## Kendala umum

| Kendala | Tindakan |
|---|---|
| Mahasiswa terblokir saat KRS | Periksa tagihan wajib, status pembayaran, periode, dan override. |
| Saldo terpotong tetapi belum lunas | Jangan membuat pembayaran baru; cocokkan referensi dan lakukan rekonsiliasi. |
| Penerima pratinjau salah | Batalkan penerbitan lalu koreksi target template/registrasi. |
| Bukti tidak dapat dibuka | Minta mahasiswa mengunggah ulang melalui ticket; jangan menebak keputusan. |
| Periode hanya-baca | Gunakan prosedur koreksi resmi atau minta Web Administrator memeriksa status periode. |

[Kembali ke daftar panduan](README.md)
