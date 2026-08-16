# Backlog Implementasi Pembukaan Tahun Akademik Baru

Dokumen ini memecah kebutuhan pembukaan tahun akademik menjadi task kecil yang dapat dikerjakan dan diverifikasi secara bertahap. Urutan task mengikuti dependensi teknis; jangan mengerjakan task fase berikutnya sebelum fondasi yang menjadi dependensinya selesai.

## Tujuan

Menyediakan proses pembukaan periode akademik yang aman, terlacak, dan tidak mencampur data periode lama dengan periode baru, mulai dari pembuatan periode, registrasi mahasiswa, kelas, penawaran mata kuliah, jadwal, KRS, sampai tagihan dan publikasi.

## Prinsip implementasi

- Gunakan migration baru; jangan mengubah migration yang sudah pernah dijalankan di produksi.
- Simpan riwayat akademik per periode. Jangan menimpa data historis ketika mahasiswa naik semester atau pindah kelas.
- Hanya boleh ada satu periode berstatus aktif pada satu waktu.
- Data baru dibuat sebagai `draft` dan baru terlihat oleh mahasiswa/dosen setelah dipublikasikan.
- Semua operasi massal harus menyediakan pratinjau, transaksi database, hasil proses, dan audit log.
- Setiap endpoint harus tetap dilindungi middleware peran dan pemeriksaan kepemilikan data.
- Tambahkan Feature test untuk jalur berhasil dan minimal satu jalur penolakan pada setiap workflow.

## Definisi status task

- `[ ]` Belum dikerjakan
- `[-]` Sedang dikerjakan
- `[x]` Selesai dan sudah diverifikasi
- `[!]` Terblokir dan memerlukan keputusan

---

## Fase 0 — Keputusan domain dan baseline

### Keputusan domain awal

- Periode akademik menggunakan `term`: `ganjil`, `genap`, atau `pendek`.
- Nomor semester mahasiswa tidak disimpan sebagai jenis periode; nomor tersebut akan menjadi bagian dari registrasi mahasiswa per periode pada Fase 3.
- Kode periode menggunakan huruf, angka, dan tanda hubung dengan contoh `2026-GANJIL`.
- Tanggal operasional mengikuti zona waktu aplikasi `Asia/Jakarta`.
- Siklus hidup periode adalah `draft` → `active` → `closed` → `archived`.
- Hanya Web Administrator yang dapat mengaktifkan periode melalui route pada area `web-admin`.
- Aktivasi periode draft menutup periode aktif sebelumnya agar hanya ada satu periode aktif.
- Periode aktif/ditutup/diarsipkan dikunci dari CRUD umum; perubahan setelah aktivasi harus memakai workflow khusus pada task berikutnya.
- Semester pendek tidak boleh aktif bersamaan dengan periode reguler pada implementasi awal.

### TA-001 — Tetapkan istilah periode akademik `[x]`

- [x] Putuskan bahwa periode menggunakan jenis `ganjil`, `genap`, atau `pendek`.
- [x] Pisahkan jenis periode dari semester mahasiswa (1–8 atau lebih).
- [x] Tentukan format kode, misalnya `2026-GANJIL`.
- [x] Dokumentasikan zona waktu tanggal akademik sebagai `Asia/Jakarta`.

**Dependensi:** Tidak ada.

**Selesai jika:** istilah, nilai enum, dan format kode disetujui serta tidak memiliki arti ganda.

### TA-002 — Tetapkan aturan siklus hidup periode `[x]`

- [x] Definisikan status `draft`, `active`, `closed`, dan `archived`.
- [x] Tentukan siapa yang boleh membuat, mengaktifkan, menutup, dan mengarsipkan periode.
- [x] Tentukan data apa yang masih boleh diubah setelah periode aktif atau ditutup.
- [x] Tentukan apakah semester pendek boleh aktif bersamaan dengan periode reguler.

**Dependensi:** TA-001.

**Selesai jika:** tersedia matriks status, aksi yang diizinkan, dan peran pelaksana.

### TA-003 — Catat baseline perilaku yang sudah ada

- [ ] Tambahkan test karakterisasi untuk CRUD tahun akademik saat ini.
- [ ] Tambahkan test karakterisasi tampilan jadwal mahasiswa berdasarkan kelas.
- [ ] Tambahkan test karakterisasi tagihan mahasiswa yang berlaku saat ini.
- [ ] Catat route dan query yang masih mengambil seluruh periode dengan `all()`.

**Dependensi:** Tidak ada.

**Verifikasi:** jalankan test terfokus tanpa mengubah hasil perilaku lama.

---

## Fase 1 — Fondasi periode akademik

### TA-101 — Tambahkan metadata periode akademik `[x]`

- [x] Buat migration baru untuk menambahkan `year_end`, `term`, `starts_at`, `ends_at`, dan `status` pada `tahun_akademiks`.
- [x] Tambahkan index untuk `code`, `status`, dan jenis periode yang sering difilter (`code` sudah memiliki unique index lama).
- [x] Isi status aman untuk record lama; metadata yang tidak dapat disimpulkan dibiarkan `null` agar dilengkapi secara eksplisit.
- [x] Perbarui cast dan konstanta status pada model `TahunAkademik`.

**Dependensi:** TA-001, TA-002.

**Selesai jika:** periode dapat menyimpan identitas, jenis semester, rentang waktu, dan status tanpa mengubah data lama.

**Verifikasi:** migration `up/down`, Unit test status, dan Feature test validasi tanggal.

### TA-102 — Perketat validasi CRUD periode `[x]`

- [x] Validasi kode periode unik ketika membuat maupun memperbarui data.
- [x] Validasi `year_end >= year_start`.
- [x] Validasi `ends_at >= starts_at`.
- [x] Gunakan `firstOrFail()` agar kode yang tidak ditemukan menghasilkan 404.
- [x] Cegah penghapusan periode yang sudah memiliki data turunan.
- [x] Tambahkan pesan validasi dalam Bahasa Indonesia.

**Dependensi:** TA-101.

**Verifikasi:** Feature test kode duplikat, tanggal tidak valid, data tidak ditemukan, dan periode yang sedang dipakai.

### TA-103 — Implementasikan aktivasi satu periode `[x]`

- [x] Tambahkan aksi `activate` dengan HTTP `PATCH`.
- [x] Jalankan aktivasi dalam transaksi database dengan row lock.
- [x] Nonaktifkan dan tutup periode aktif sebelumnya ketika periode baru diaktifkan.
- [x] Tolak aktivasi periode yang belum memenuhi syarat minimum.
- [x] Catat pengguna dan waktu aktivasi.

**Dependensi:** TA-102.

**Selesai jika:** dalam kondisi normal hanya satu periode yang memiliki status aktif.

**Verifikasi:** Feature test aktivasi pertama, pergantian periode aktif, akses tanpa wewenang, dan rollback saat gagal.

### TA-104 — Tambahkan penutupan dan pengarsipan periode

- [ ] Tambahkan aksi `close` dan `archive`.
- [ ] Cegah perubahan akademik tertentu setelah periode ditutup.
- [ ] Cegah periode yang diarsipkan diaktifkan kembali tanpa prosedur khusus.
- [ ] Tampilkan konfirmasi dan dampak aksi pada UI.

**Dependensi:** TA-103.

**Verifikasi:** Feature test transisi status valid dan tidak valid.

### TA-105 — Tambahkan kalender akademik

- [ ] Buat tabel `kalender_akademiks` yang terhubung ke periode.
- [ ] Simpan nama kegiatan, kategori, waktu mulai/selesai, dan status publikasi.
- [ ] Buat CRUD kalender untuk Web Admin/Akademik.
- [ ] Tampilkan kalender terpublikasi pada portal mahasiswa dan dosen.

**Dependensi:** TA-101.

**Verifikasi:** Feature test CRUD, otorisasi, rentang tanggal, dan visibilitas publikasi.

---

## Fase 2 — Konteks dan pemisahan data per periode

### TA-201 — Buat layanan pencari periode aktif `[x]`

- [x] Buat `AcademicPeriodContext` sebagai satu sumber untuk memperoleh periode aktif.
- [x] Kembalikan `null` dan tampilkan status belum ada periode ketika periode aktif belum tersedia.
- [x] Hindari pemanggilan `TahunAkademik::all()` untuk menentukan konteks operasional.
- [x] Tunda cache sampai perilaku dasar teruji dan kebutuhan performanya terbukti.

**Dependensi:** TA-103.

**Verifikasi:** Unit test saat ada satu periode aktif dan saat tidak ada periode aktif.

### TA-202 — Tambahkan pemilih periode untuk staf `[x]`

- [x] Tambahkan session `selected_taka_id` untuk tampilan staf.
- [x] Batasi pilihan historis untuk Web Administrator, Keuangan, Akademik, dan Admin; Officer/Support hanya periode aktif.
- [x] Gunakan periode aktif sebagai nilai awal dan fallback session yang tidak valid.
- [x] Tampilkan periode yang sedang dipilih pada header layout admin.
- [x] Endpoint berada di route staf dan pemilih tidak ditampilkan pada guard mahasiswa/dosen.

**Dependensi:** TA-201.

**Verifikasi:** Feature test pemilihan periode, session, ID tidak valid, dan akses lintas peran.

### TA-203 — Filter kelas berdasarkan periode `[x]`

- [x] Tambahkan scope `forAcademicPeriod` pada model `Kelas`.
- [x] Filter daftar, program kuliah pada form, export, dan import kelas berdasarkan konteks periode.
- [x] Pastikan detail, cetak, update, dan hapus kelas tidak dapat membuka kelas dari konteks lain.
- [x] Tambahkan empty state dan nonaktifkan aksi jika periode belum tersedia.
- [x] Jadikan periode ditutup/diarsipkan hanya-baca untuk seluruh mutasi kelas.

**Dependensi:** TA-202.

**Verifikasi:** Feature test dua periode dengan kelas yang berbeda.

### TA-204 — Filter mata kuliah yang ditawarkan berdasarkan periode

- [x] Tambahkan scope periode pada `MataKuliah` atau entitas penawaran hasil TA-401.
- [x] Filter daftar, form, import, export, dan prasyarat berdasarkan periode/prodi.
- [x] Jangan menampilkan penawaran periode lama pada form jadwal baru.

**Dependensi:** TA-202; sesuaikan kembali setelah TA-401.

**Verifikasi:** Feature test pemisahan penawaran pada dua periode.

### TA-205 — Filter jadwal dan presensi berdasarkan periode

- [x] Turunkan konteks periode melalui kelas/penawaran mata kuliah.
- [x] Filter portal staf, mahasiswa, dan dosen.
- [x] Tolak akses langsung ke kode jadwal dari periode yang tidak diizinkan.
- [x] Pastikan cetak presensi menggunakan konteks periode yang benar.

**Dependensi:** TA-203, TA-204.

**Verifikasi:** Feature test akses jadwal pada periode aktif dan penolakan akses periode lain.

### TA-206 — Filter nilai dan hasil studi berdasarkan periode

- [x] Kaitkan nilai dengan registrasi/penawaran/periode secara eksplisit.
- [x] Filter input nilai dosen dan tampilan nilai mahasiswa berdasarkan periode.
- [x] Pastikan transkrip tetap dapat menggabungkan semua periode.
- [x] Cegah perubahan nilai setelah periode ditutup, kecuali melalui prosedur koreksi.

> Catatan implementasi: tabel TA-301 sudah tersedia. Sampai backfill TA-302 dan migrasi konsumen registrasi selesai, validasi nilai tetap menggunakan kelas dan penawaran mata kuliah pada periode yang sama. Kolom `taka_id` disimpan langsung pada nilai agar konteks tidak perlu ditebak ulang.

**Dependensi:** TA-104, TA-204, TA-301.

**Verifikasi:** Feature test pemisahan nilai dan penguncian periode tertutup.

---

## Fase 3 — Registrasi mahasiswa dan riwayat kelas

### TA-301 — Buat tabel registrasi mahasiswa per periode

- [x] Buat tabel `registrasi_mahasiswas`.
- [x] Simpan `mahasiswa_id`, `taka_id`, semester mahasiswa, status akademik, status registrasi, `kelas_id`, `dosen_wali_id`, dan batas SKS.
- [x] Tambahkan unique constraint mahasiswa-periode.
- [x] Tambahkan foreign key/index yang diperlukan.
- [x] Buat model dan relasi Eloquent.

**Dependensi:** TA-101.

**Selesai jika:** perubahan kelas atau semester tidak menghilangkan riwayat periode sebelumnya.

**Verifikasi:** migration, factory, relasi model, dan test unique constraint.

### TA-302 — Backfill registrasi mahasiswa lama

- [x] Buat command Artisan khusus untuk membentuk registrasi dari `mahasiswas.taka_id` dan `class_id` lama.
- [x] Tambahkan mode `--dry-run`.
- [x] Laporkan record berhasil, dilewati, dan gagal.
- [x] Jadikan command idempotent.
- [x] Jangan menghapus kolom lama pada task ini.

Command: `php artisan academic:backfill-registrations --dry-run`, lalu jalankan tanpa `--dry-run` setelah laporan valid.

> Data lama tidak menyimpan semester kemajuan mahasiswa secara terpisah. Backfill awal memakai nilai semester mentah pada tahun akademik (1/2); hasilnya perlu ditinjau sebelum proses kenaikan semester massal.

**Dependensi:** TA-301.

**Verifikasi:** test command dengan data valid, data tidak lengkap, dan eksekusi ulang.

### TA-303 — Implementasikan status akademik mahasiswa `[x]`

- [x] Definisikan status `aktif`, `cuti`, `nonaktif`, `lulus`, `drop_out`, dan `mengundurkan_diri`.
- [x] Tambahkan validasi transisi status.
- [x] Simpan alasan, tanggal berlaku, dan aktor perubahan.
- [x] Batasi mahasiswa nonaktif/cuti dari KRS sesuai kebijakan.

Kebijakan transisi: status `aktif` dapat berubah ke seluruh status nonaktif/terminal; `cuti` dan `nonaktif` dapat kembali `aktif` atau berpindah di antara keduanya; status `lulus`, `drop_out`, dan `mengundurkan_diri` bersifat terminal. Perubahan hanya diizinkan pada periode draft/aktif, wajib memiliki alasan dan tanggal berlaku yang tidak berada di masa depan, serta dicatat pada `riwayat_status_akademik_mahasiswas`.

Kelayakan KRS dipusatkan pada `KrsEligibilityService`: registrasi harus berstatus akademik `aktif` dan berstatus registrasi `terdaftar`. Layanan ini menjadi guard yang wajib dipakai saat TA-405 mengimplementasikan penyimpanan KRS.

**Dependensi:** TA-301.

**Verifikasi:** Unit test transisi dan Feature test otorisasi perubahan status.

### TA-304 — Buat proses registrasi ulang individual `[x]`

- [x] Sediakan form registrasi mahasiswa ke periode baru.
- [x] Pilih semester, status, kelas, dosen wali, dan batas SKS.
- [x] Cegah registrasi ganda pada periode yang sama.
- [x] Tampilkan riwayat registrasi pada detail mahasiswa.

Registrasi individual menggunakan periode yang dipilih staf, hanya menerima kelas dari periode tersebut, dan menyimpan `status_registrasi=terdaftar`. Mahasiswa dengan status terakhir `lulus`, `drop_out`, atau `mengundurkan_diri` tidak dapat diregistrasikan kembali. Selama masa transisi menuju TA-306, `mahasiswas.taka_id` dan `class_id` ikut disinkronkan tanpa mengubah riwayat registrasi sebelumnya.

**Dependensi:** TA-301, TA-303.

**Verifikasi:** Feature test registrasi berhasil, duplikat, dan mahasiswa tidak ditemukan.

### TA-305 — Buat proses kenaikan semester massal `[x]`

- [x] Pilih periode sumber dan periode tujuan.
- [x] Buat pratinjau mahasiswa yang akan diproses.
- [x] Abaikan atau tandai mahasiswa lulus, DO, dan mengundurkan diri.
- [x] Sediakan keputusan eksplisit untuk mahasiswa cuti/nonaktif.
- [x] Jalankan pembuatan registrasi dalam transaksi/chunk yang aman.
- [x] Simpan ringkasan dan audit hasil.

Proses dilakukan per kelas sumber dan kelas tujuan. Mahasiswa cuti/nonaktif harus dipilih untuk diaktifkan, dipertahankan statusnya, atau dilewati. Setiap mahasiswa diproses dalam transaksi tersendiri dan sumber dibaca dengan chunk; eksekusi ulang melewati registrasi tujuan yang sudah tersedia. Ringkasan berhasil/dilewati/gagal beserta aktor dan konfigurasi disimpan pada `proses_kenaikan_semesters`.

**Dependensi:** TA-304.

**Verifikasi:** Feature test pratinjau, proses massal, eksekusi ulang, dan rollback.

### TA-306 — Migrasikan pembacaan kelas mahasiswa ke registrasi `[x]`

- [x] Ubah dashboard, profil, jadwal, tugas, presensi, dan nilai agar memakai registrasi periode.
- [x] Pertahankan fallback sementara untuk data yang belum dibackfill.
- [x] Tambahkan deprecation note untuk `mahasiswas.taka_id`, `years_id`, dan `class_id`.
- [x] Hapus fallback hanya setelah audit data produksi selesai pada deployment terpisah.

`StudentAcademicContext` menjadi resolver kelas/registrasi mahasiswa. Registrasi periode selalu diutamakan; fallback legacy hanya dipakai jika registrasi periode belum tersedia dan kelas lama benar-benar berasal dari periode yang diminta. Roster kelas, presensi, dan input nilai memakai scope `Mahasiswa::forAcademicClass()` dengan aturan fallback yang sama. Kolom legacy belum dihapus dan penghapusannya wajib dilakukan pada deployment terpisah setelah audit produksi.

**Dependensi:** TA-302, TA-304.

**Verifikasi:** regression test seluruh area mahasiswa yang sebelumnya membaca `class_id`.

---

## Fase 4 — Penawaran mata kuliah dan KRS

### TA-401 — Pisahkan master mata kuliah dari penawaran periode

- [x] Pertahankan `master_mata_kuliahs` sebagai identitas mata kuliah.
- [x] Buat entitas `penawaran_mata_kuliahs` untuk periode, prodi, kurikulum, kelas, kapasitas, dan dosen.
- [x] Tentukan strategi migrasi data `mata_kuliahs` lama.
- [x] Tambahkan unique constraint yang mencegah penawaran ganda pada kombinasi yang sama.

Data `mata_kuliahs` lama yang sudah memiliki master dan kelas dibackfill otomatis ke penawaran baru. Kolom `legacy_mata_kuliah_id` mempertahankan jejak asal, sedangkan jadwal dan nilai mendapat referensi penawaran baru tanpa menghapus relasi lama selama masa kompatibilitas.

**Dependensi:** TA-101, TA-203.

**Verifikasi:** migration, relasi, dan test duplikasi penawaran.

### TA-402 — Buat CRUD penawaran mata kuliah

- [x] Buat penawaran untuk satu atau beberapa kelas.
- [x] Tetapkan dosen utama dan dosen pendamping.
- [x] Tetapkan kapasitas dan prasyarat.
- [x] Filter pilihan berdasarkan periode, prodi, dan kurikulum.
- [x] Cegah perubahan berbahaya setelah KRS memiliki peserta.

Identitas akademik penawaran terkunci setelah memiliki item KRS. Dosen dan kapasitas masih dapat diperbarui, tetapi kapasitas tidak boleh lebih kecil dari peserta yang telah tercatat.

**Dependensi:** TA-401.

**Verifikasi:** Feature test CRUD, otorisasi, filter, dan penguncian penawaran terpakai.

### TA-403 — Tambahkan fitur salin penawaran

- [x] Pilih periode sumber dan tujuan.
- [x] Tampilkan pratinjau data yang disalin.
- [x] Izinkan penyesuaian dosen dan kelas yang tidak lagi tersedia.
- [x] Lewati duplikat secara terukur dan tampilkan hasilnya.

Proses salin berjalan dalam transaksi dan menampilkan jumlah berhasil, duplikat dilewati, serta referensi tidak valid. Kelas dan dosen dapat dipetakan ulang pada halaman pratinjau.

**Dependensi:** TA-402.

**Verifikasi:** Feature test salin, duplikat, referensi tidak valid, dan rollback.

### TA-404 — Buat tabel KRS dan detail KRS

- [x] Buat header `krs` yang terhubung ke registrasi mahasiswa.
- [x] Buat `krs_items` yang terhubung ke penawaran mata kuliah.
- [x] Simpan status `draft`, `submitted`, `approved`, `rejected`, dan `locked`.
- [x] Tambahkan unique constraint agar satu penawaran tidak dipilih dua kali.
- [x] Simpan total SKS sebagai hasil terhitung atau snapshot yang tervalidasi.

Satu registrasi hanya memiliki satu header KRS. Snapshot SKS dihitung ulang setiap item draft berubah; item tidak dapat dimutasi setelah KRS diajukan atau disetujui.

**Dependensi:** TA-301, TA-401.

**Verifikasi:** migration, relasi, constraint, dan Unit test perhitungan SKS.

### TA-405 — Implementasikan pengisian KRS mahasiswa

- [x] Batasi pengisian pada jadwal KRS di kalender akademik.
- [x] Hanya izinkan mahasiswa dengan registrasi/status yang memenuhi syarat.
- [x] Validasi kapasitas, prasyarat, mata kuliah pernah lulus, batas SKS, dan duplikasi.
- [x] Simpan draft sebelum diajukan.
- [x] Tampilkan ringkasan SKS dan pesan validasi Bahasa Indonesia.

Fondasi kalender kategori `krs` ditambahkan sebagai dependensi minimal TA-105. Staf dapat mengatur rentang waktu dan status publikasi langsung dari halaman penawaran.

**Dependensi:** TA-404, TA-105, TA-303.

**Verifikasi:** Feature test jalur berhasil dan setiap penolakan utama.

### TA-406 — Implementasikan persetujuan dosen wali

- [x] Tampilkan KRS yang diajukan hanya kepada dosen wali terkait.
- [x] Izinkan setujui atau tolak dengan catatan.
- [x] Kunci item setelah KRS disetujui.
- [x] Catat aktor dan waktu keputusan.
- [x] Kirim notifikasi internal kepada mahasiswa.

Penolakan mewajibkan catatan. Keputusan mencatat dosen wali dan waktu, kemudian membuat notifikasi internal yang ditujukan langsung kepada mahasiswa.

**Dependensi:** TA-405.

**Verifikasi:** Feature test kepemilikan dosen wali, persetujuan, penolakan, dan KRS terkunci.

### TA-407 — Tambahkan cetak KRS dan daftar peserta

- [x] Buat cetak KRS mahasiswa dengan identitas periode.
- [x] Buat daftar peserta per penawaran berdasarkan KRS disetujui.
- [x] Ubah presensi dan input nilai agar sumber peserta berasal dari KRS disetujui.

Jadwal mahasiswa, akses presensi, cetak daftar presensi, dan roster input nilai memakai KRS `approved`/`locked` ketika jadwal atau mata kuliah telah dipetakan ke penawaran. Fallback kelas hanya dipertahankan untuk record legacy yang belum memiliki pemetaan penawaran.

**Dependensi:** TA-406.

**Verifikasi:** Feature test isi dokumen dan pengecualian KRS yang belum disetujui.

---

## Fase 5 — Jadwal dan pertemuan kuliah

### TA-501 — Pisahkan jadwal mingguan dan pertemuan

- [x] Jadikan jadwal mingguan menyimpan penawaran, kelas, dosen, ruang, hari, dan jam.
- [x] Buat tabel pertemuan yang menyimpan tanggal, pertemuan ke-, metode, materi, dan status.
- [x] Rencanakan migrasi record `jadwal_kuliahs` lama tanpa kehilangan presensi.
- [x] Pertahankan kode publik yang dibutuhkan route lama selama masa transisi.

`jadwal_mingguans` menyimpan pola berulang, sedangkan `pertemuan_kuliahs` menyimpan snapshot tanggal, dosen, ruang, jam, metode, materi, dan status. Migrasi mengelompokkan pola lama menggunakan fingerprint dan menjadikan setiap `jadwal_kuliahs` lama sebagai pertemuan dengan kode publik yang tetap sama. Record legacy dipertahankan sebagai adapter route selama transisi.

**Dependensi:** TA-401.

**Verifikasi:** migration, relasi, dan regression test presensi.

### TA-502 — Tambahkan validasi bentrok jadwal

- [x] Deteksi bentrok dosen pada hari dan rentang waktu yang sama.
- [x] Deteksi bentrok kelas.
- [x] Deteksi bentrok ruang.
- [x] Validasi kapasitas ruang terhadap peserta/kapasitas penawaran.
- [x] Izinkan pengecualian hanya dengan alasan dan wewenang yang jelas jika diperlukan.

Rentang dianggap bentrok ketika waktu mulai salah satu jadwal berada sebelum waktu selesai jadwal lain dan sebaliknya. Kapasitas ruang menjadi field wajib. Pengecualian hanya dapat dilakukan Web Administrator dengan alasan minimal 10 karakter dan aktornya disimpan pada jadwal.

**Dependensi:** TA-501.

**Verifikasi:** Unit test rentang waktu dan Feature test setiap jenis bentrok.

### TA-503 — Buat generator pertemuan semester

- [x] Hasilkan pertemuan dari jadwal mingguan dan rentang kalender.
- [x] Lewati hari libur kalender akademik.
- [x] Izinkan jumlah pertemuan dikonfigurasi.
- [x] Sediakan pratinjau sebelum menyimpan.
- [x] Cegah pembuatan pertemuan duplikat.

Generator memakai rentang tanggal dan jumlah 1–20 pertemuan, melewati kalender terpublikasi berkategori `libur`, serta menampilkan tanggal yang akan dibuat atau dilewati. Constraint nomor dan tanggal per jadwal membuat eksekusi ulang idempotent.

**Dependensi:** TA-105, TA-501.

**Verifikasi:** Unit test kalender/libur dan Feature test generator idempotent.

### TA-504 — Integrasikan presensi dengan pertemuan

- [x] Kaitkan presensi mahasiswa ke pertemuan dan peserta KRS.
- [x] Cegah mahasiswa di luar peserta mengambil presensi.
- [x] Pertahankan riwayat presensi saat jadwal mingguan diperbarui.
- [x] Perbarui cetak rekap presensi per periode.

Presensi baru menyimpan `pertemuan_kuliah_id` dan `krs_item_id`; item KRS wajib berstatus `approved`/`locked`. Presensi historis dibackfill ke pertemuan, sedangkan `krs_item_id` dibiarkan kosong jika data lama memang belum memiliki KRS yang dapat dibuktikan. Pertemuan menyimpan snapshot sehingga perubahan pola mingguan tidak menulis ulang riwayat. Rekap periode tersedia dari halaman jadwal mingguan.

**Dependensi:** TA-407, TA-503.

**Verifikasi:** Feature test peserta sah, bukan peserta, periode berbeda, dan jadwal berubah.

---

## Fase 6 — Tagihan dan syarat keuangan

### TA-601 — Kaitkan tagihan dengan periode akademik

- [x] Buat migration baru untuk `taka_id`, tanggal terbit, jatuh tempo, status, dan jenis tagihan.
- [x] Ubah nominal menjadi tipe decimal/integer rupiah yang konsisten, bukan string bebas.
- [x] Tambahkan index periode/status/jatuh tempo.
- [x] Siapkan backfill tagihan lama tanpa menebak periode secara diam-diam.

Tagihan dan riwayat pembayaran kini menyimpan identitas periode serta snapshot nominal integer. Backfill hanya menetapkan periode jika dapat dibuktikan dari program kuliah atau tepat satu registrasi yang cocok dengan tanggal tagihan; data ambigu tetap `null` untuk ditinjau manual.

**Dependensi:** TA-101.

**Verifikasi:** migration, cast nominal, dan test validasi tanggal/nominal.

### TA-602 — Perketat target penerima tagihan

- [x] Gunakan foreign key nullable untuk mahasiswa, prodi, program kuliah, atau kelompok target.
- [x] Pastikan tepat satu jenis target dipilih.
- [x] Validasi target berada pada konteks periode yang sesuai.
- [x] Cegah tagihan duplikat untuk jenis, periode, dan penerima yang sama.

`BillingTargetService` memvalidasi mahasiswa, prodi, program kuliah, atau kelompok status terhadap registrasi pada periode template. Tagihan hasil penerbitan dinormalisasi menjadi target mahasiswa dan dilindungi constraint jenis/periode/penerima.

**Dependensi:** TA-601, TA-301.

**Verifikasi:** Feature test setiap jenis target, lintas periode, dan duplikasi.

### TA-603 — Implementasikan penerbitan tagihan massal

- [x] Buat template tagihan per periode.
- [x] Tampilkan daftar calon penerima dan total nominal sebagai pratinjau.
- [x] Jalankan penerbitan dalam transaksi/chunk.
- [x] Catat penerima berhasil, dilewati, dan gagal.
- [x] Jadikan proses aman saat dijalankan ulang.

Halaman Keuangan Periode menyediakan template dan pratinjau tanpa mutasi. Penerbitan berjalan dalam transaksi per batch dan kandidat diproses per chunk; jumlah berhasil, dilewati, serta gagal disimpan sehingga eksekusi ulang dapat diaudit.

**Dependensi:** TA-602.

**Verifikasi:** Feature test pratinjau, penerbitan, idempotensi, dan rollback.

### TA-604 — Tambahkan kebijakan pembayaran sebagai syarat KRS

- [x] Tentukan jenis tagihan yang wajib lunas sebelum KRS.
- [x] Buat service pengecekan kelayakan keuangan.
- [x] Tampilkan alasan blokir kepada mahasiswa tanpa membuka data sensitif.
- [x] Sediakan override terbatas dengan alasan dan audit log jika kebijakan mengizinkan.

Template dapat ditandai wajib lunas untuk KRS. `FinancialEligibilityService` diintegrasikan ke kelayakan KRS dan hanya mengembalikan alasan administrasi generik. Override dibatasi untuk Web Administrator/Finance, wajib beralasan, dapat kedaluwarsa, dan menyimpan aktor audit.

**Dependensi:** TA-405, TA-603.

**Verifikasi:** Feature test lunas, belum lunas, tagihan tidak wajib, dan override.

### TA-605 — Pisahkan laporan keuangan per periode

- [x] Filter tagihan, pembayaran, tunggakan, dan pendapatan berdasarkan periode.
- [x] Tambahkan rekap per prodi/program kuliah/status mahasiswa.
- [x] Pastikan total periode lama tidak berubah ketika periode baru dibuat.

Laporan menggunakan konteks periode terpilih dan menampilkan total tagihan, pembayaran, tunggakan, serta rincian program studi, program kuliah, dan status mahasiswa. Pengujian dua periode memastikan penambahan periode baru tidak mengubah agregat periode lama.

**Dependensi:** TA-601.

**Verifikasi:** Feature test agregasi dua periode dengan nominal berbeda.

---

## Fase 7 — Workflow pembukaan dan publikasi

### TA-701 — Buat pemeriksa kesiapan periode

- [x] Periksa identitas dan tanggal periode.
- [x] Periksa kurikulum/prodi yang diperlukan.
- [x] Periksa registrasi mahasiswa dan kelas.
- [x] Periksa penawaran mata kuliah dan dosen.
- [x] Periksa bentrok jadwal.
- [x] Periksa tagihan wajib.
- [x] Kembalikan hasil `siap`, `peringatan`, atau `gagal` beserta tautan perbaikannya.

`PeriodReadinessService` menggabungkan enam kelompok pemeriksaan dan dapat menyimpan snapshot immutable untuk pemeriksaan manual maupun publikasi.

**Dependensi:** TA-304, TA-402, TA-502, TA-603.

**Verifikasi:** Unit test setiap rule dan Feature test ringkasan kesiapan.

### TA-702 — Buat dashboard pembukaan periode

- [x] Tampilkan progres per kelompok kebutuhan.
- [x] Tampilkan jumlah data lengkap, peringatan, dan kegagalan.
- [x] Sediakan tautan langsung ke task operasional yang belum selesai.
- [x] Batasi dashboard sesuai peran departemen.

Dashboard menampilkan seluruh kelompok untuk Web Administrator, pemeriksaan keuangan untuk Finance, dan pemeriksaan akademik untuk Academic/Admin. Officer dan Support ditolak.

**Dependensi:** TA-701.

**Verifikasi:** Feature test tampilan progres dan otorisasi peran.

### TA-703 — Implementasikan publikasi periode

- [x] Pisahkan aktivasi internal dari publikasi ke portal mahasiswa/dosen jika diperlukan.
- [x] Tolak publikasi jika pemeriksaan wajib gagal.
- [x] Jalankan publikasi dalam transaksi.
- [x] Catat snapshot ringkasan kesiapan, aktor, dan waktu publikasi.
- [x] Kirim notifikasi internal setelah publikasi berhasil.

Portal mahasiswa dan dosen hanya menggunakan periode `active` yang juga `is_published`. Publikasi hanya dapat dilakukan Web Administrator dan menyimpan snapshot serta notifikasi internal dalam transaksi.

**Dependensi:** TA-701, TA-103.

**Verifikasi:** Feature test publikasi siap, penolakan belum siap, otorisasi, dan rollback.

### TA-704 — Tambahkan fitur salin konfigurasi periode

- [x] Pilih periode sumber dan tujuan yang masih draft.
- [x] Izinkan memilih data: kelas, penawaran, dosen, jadwal, atau template tagihan.
- [x] Tampilkan pratinjau dan konflik referensi.
- [x] Jangan menyalin KRS, nilai, pembayaran, atau presensi.
- [x] Catat hasil proses secara rinci.

Penyalinan bersifat idempoten, memvalidasi dependensi pilihan, dan menyimpan jumlah dibuat, dilewati, serta konflik pada setiap proses.

**Dependensi:** TA-403, TA-503, TA-603.

**Verifikasi:** Feature test pilihan data, pengecualian data transaksi, konflik, dan idempotensi.

---

## Fase 8 — Audit, keamanan, dan operasional produksi

### TA-801 — Tambahkan audit log workflow akademik

- [x] Catat perubahan status periode.
- [x] Catat registrasi/kenaikan semester massal.
- [x] Catat perubahan status mahasiswa.
- [x] Catat persetujuan KRS dan override keuangan.
- [x] Catat penerbitan atau pembatalan tagihan.
- [x] Hindari menyimpan kredensial atau data pembayaran sensitif dalam log.

Audit workflow menyimpan aktor lintas guard, subjek, before/after, dan metadata yang disanitasi tanpa token/kredensial pembayaran.

**Dependensi:** dapat dimulai setelah TA-103 dan diperluas per fase.

**Verifikasi:** Feature test event penting menghasilkan audit dengan aktor yang benar.

### TA-802 — Tambahkan foreign key dan perlindungan integritas

- [x] Audit seluruh kolom `*_id` terkait akademik.
- [x] Tambahkan foreign key secara bertahap setelah data yatim dibersihkan.
- [x] Pilih kebijakan `restrict`, `cascade`, atau `set null` secara eksplisit.
- [x] Tambahkan unique constraint bisnis yang belum tersedia.
- [x] Hindari penghapusan fisik data transaksi akademik.

Dashboard Web Administrator memeriksa referensi yatim pada relasi inti. Schema normalisasi memakai FK dan constraint bisnis; tagihan periode dibatalkan secara status dan record akademik yang sudah bertransaksi dilindungi dari penghapusan.

**Dependensi:** model data fase 1–6 stabil.

**Verifikasi:** migration pada salinan database dan test penghapusan record yang masih dipakai.

### TA-803 — Hardening import/export per periode

- [x] Tambahkan periode eksplisit pada template import/export.
- [x] Validasi tipe/ukuran file, header, referensi, dan duplikasi.
- [x] Tambahkan dry-run dan laporan baris gagal.
- [x] Batasi import pada periode draft jika perubahan berisiko.
- [x] Pastikan file tidak dapat mengakses periode di luar kewenangan pengguna.

Import mahasiswa, kelas, mata kuliah, dan jadwal memakai konteks periode terpilih, file lokal sementara, validasi baris, serta `dry_run`. Mutasi import periode dibatasi ke draft; periode aktif hanya dapat diperiksa dengan dry-run.

**Dependensi:** TA-202 dan entitas terkait sudah stabil.

**Verifikasi:** Feature test file valid, format salah, referensi lintas periode, dan duplikasi.

### TA-804 — Uji workflow end-to-end pembukaan periode

- [x] Buat periode draft.
- [x] Registrasikan mahasiswa dan bentuk kelas.
- [x] Buat penawaran dan jadwal tanpa bentrok.
- [x] Terbitkan tagihan.
- [x] Aktifkan dan publikasikan periode.
- [x] Isi dan setujui KRS.
- [x] Pastikan mahasiswa/dosen hanya melihat periode yang benar.
- [x] Tutup periode dan pastikan data akademik terkunci.

Feature test membuka periode lengkap, mempublikasikannya, menjalankan KRS hingga persetujuan, membuktikan isolasi portal, lalu memastikan periode tertutup menolak mutasi KRS.

**Dependensi:** TA-703 dan seluruh fitur wajib yang digunakan workflow.

**Verifikasi:** Feature test end-to-end dengan minimal dua periode untuk membuktikan isolasi data.

### TA-805 — Siapkan runbook deployment

- [x] Dokumentasikan urutan deployment, migration, backfill, dan aktivasi fitur.
- [x] Siapkan backup dan langkah rollback non-destruktif.
- [x] Jalankan migration/backfill lebih dahulu pada salinan database produksi.
- [x] Catat query pemeriksaan data sebelum dan sesudah deployment.
- [x] Tentukan periode maintenance dan penanggung jawab persetujuan.

Runbook tersedia pada `docs/RUNBOOK_DEPLOYMENT_TAHUN_AKADEMIK.md` dan melarang refresh/fresh serta rollback destruktif terhadap data transaksi.

**Dependensi:** TA-302, TA-601, TA-802, TA-804.

**Selesai jika:** tim dapat menjalankan deployment dan rollback tanpa asumsi yang tidak terdokumentasi.

---

## Milestone yang disarankan

### Milestone 1 — Periode aman

Mencakup TA-001 sampai TA-205. Hasilnya adalah satu periode aktif dan daftar data operasional terpisah per periode.

### Milestone 2 — Riwayat mahasiswa aman

Mencakup TA-301 sampai TA-306. Hasilnya adalah registrasi dan kelas mahasiswa tidak lagi menimpa riwayat lama.

### Milestone 3 — KRS dapat digunakan

Mencakup TA-401 sampai TA-407. Hasilnya adalah penawaran mata kuliah, KRS mahasiswa, dan persetujuan dosen wali.

### Milestone 4 — Jadwal dan presensi konsisten

Mencakup TA-501 sampai TA-504. Hasilnya adalah jadwal bebas bentrok dan pertemuan terhubung ke peserta sah.

### Milestone 5 — Keuangan per periode

Mencakup TA-601 sampai TA-605. Hasilnya adalah tagihan, pembayaran, dan syarat KRS dapat dipisahkan per periode.

### Milestone 6 — Pembukaan periode terpandu

Mencakup TA-701 sampai TA-805. Hasilnya adalah dashboard kesiapan, publikasi terkontrol, audit, dan runbook produksi.

## Definition of Done setiap task kode

Sebuah task implementasi dianggap selesai hanya jika:

- [ ] Migration/model/controller/view/route yang relevan sudah konsisten.
- [ ] Validasi dan otorisasi telah diterapkan.
- [ ] Feature atau Unit test untuk jalur berhasil dan penolakan penting telah lulus.
- [ ] Changed PHP files lulus Laravel Pint.
- [ ] Route baru sudah diperiksa melalui `php artisan route:list` terfilter.
- [ ] Tidak ada secret, generated build, upload, atau perubahan tidak terkait dalam diff.
- [ ] Dokumentasi deployment/backfill diperbarui jika perubahan menyentuh database.

## Urutan task pertama yang siap dikerjakan

1. TA-001 — Tetapkan istilah periode akademik.
2. TA-002 — Tetapkan aturan siklus hidup periode.
3. TA-003 — Catat baseline perilaku.
4. TA-101 — Tambahkan metadata periode.
5. TA-102 — Perketat validasi CRUD periode.
6. TA-103 — Implementasikan aktivasi satu periode.
7. TA-201 — Buat layanan pencari periode aktif.
8. TA-202 — Tambahkan pemilih periode untuk staf.

Setelah urutan tersebut selesai, pemisahan kelas, mata kuliah, dan jadwal per periode dapat dilakukan dengan risiko yang lebih kecil.
