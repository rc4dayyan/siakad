# Runbook Deployment Workflow Tahun Akademik

Runbook ini digunakan untuk deployment perubahan TA-001 sampai TA-805. Semua perintah dijalankan oleh operator yang telah ditunjuk pada lingkungan yang benar. Jangan menyalin `.env`, credential database, atau kunci Midtrans ke tiket maupun log deployment.

## Penanggung jawab dan jendela maintenance

| Peran | Tanggung jawab |
|---|---|
| Release operator | Backup, deploy kode, migration, dan pencatatan hasil |
| Web Administrator | Memeriksa integritas, mengaktifkan, dan mempublikasikan periode |
| Departemen Academic | Memvalidasi registrasi, kelas, penawaran, jadwal, dan KRS |
| Departemen Finance | Memvalidasi template, penerbitan tagihan, dan pembayaran |
| Approver/pimpinan | Memberi keputusan go/no-go dan menyetujui rollback |

Jadwalkan maintenance ketika tidak ada import, pembayaran, pengisian KRS, input nilai, atau presensi. Mulai hanya setelah operator dan approver menyatakan siap. Estimasi jendela harus memasukkan waktu backup dan verifikasi salinan database, bukan hanya waktu migration.

## 1. Latihan pada salinan produksi

1. Buat salinan database produksi yang sudah disamarkan dan batasi aksesnya.
2. Deploy commit/rilis yang sama dengan kandidat produksi.
3. Jalankan `php artisan migrate:status`, lalu `php artisan migrate --force` pada salinan.
4. Jalankan backfill registrasi terlebih dahulu dalam mode kering:

   ```bash
   php artisan academic:backfill-registrations --dry-run
   php artisan academic:backfill-registrations
   ```

5. Buka Dashboard Pembukaan Periode dan pastikan audit integritas menunjukkan nol referensi yatim.
6. Jalankan seluruh test otomatis dan simulasi publikasi menggunakan data nonproduksi.
7. Catat durasi, jumlah data yang dipetakan/dilewati, dan masalah yang ditemukan. Jangan lanjut ke produksi bila salinan gagal.

## 2. Pemeriksaan sebelum deployment

Simpan hasil query berikut sebagai artefak deployment tanpa data pribadi:

```sql
SELECT status, is_active, COUNT(*) jumlah FROM tahun_akademiks GROUP BY status, is_active;
SELECT COUNT(*) registrasi FROM registrasi_mahasiswas;
SELECT COUNT(*) penawaran FROM penawaran_mata_kuliahs;
SELECT COUNT(*) jadwal FROM jadwal_mingguans;
SELECT COUNT(*) tagihan, SUM(nominal) nominal FROM tagihan_kuliahs WHERE status = 'terbit';
SELECT COUNT(*) pembayaran, SUM(nominal) nominal FROM history_tagihans WHERE status = 'lunas';
```

Pastikan tidak ada proses batch aktif pada `proses_kenaikan_semesters`, `penerbitan_tagihan_batches`, atau `period_copy_runs`. Pastikan queue dan scheduler dapat dihentikan secara terkontrol.

## 3. Backup

1. Aktifkan maintenance mode: `php artisan down --retry=60`.
2. Hentikan worker queue setelah pekerjaan aktif selesai.
3. Buat backup database konsisten menggunakan fasilitas backup resmi penyedia/MySQL. Nama backup harus memuat waktu, lingkungan, dan release ID.
4. Verifikasi backup dapat dibaca dan catat checksum/restore point.
5. Simpan salinan file upload yang terkait dokumen akademik sesuai kebijakan retensi.

Jangan menggunakan `migrate:fresh`, `migrate:refresh`, `seed.sh`, atau menghapus tabel untuk rollback.

## 4. Urutan deployment produksi

```bash
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate:status
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika aset frontend berubah, bangun aset pada pipeline rilis sebelum deployment. Setelah migration, jalankan backfill yang sebelumnya telah lolos pada salinan produksi. Jangan menjalankan seeder demo pada produksi.

## 5. Pemeriksaan setelah deployment

1. Jalankan kembali query pra-deployment dan bandingkan total per periode.
2. Pastikan tepat nol atau satu periode internal aktif:

   ```sql
   SELECT COUNT(*) periode_aktif FROM tahun_akademiks WHERE status = 'active' AND is_active = 1;
   SELECT COUNT(*) periode_publik FROM tahun_akademiks WHERE status = 'active' AND is_active = 1 AND is_published = 1;
   ```

3. Periksa Dashboard Pembukaan Periode dan simpan snapshot readiness.
4. Verifikasi Finance hanya melihat pemeriksaan tagihan dan Academic hanya melihat pemeriksaan akademik.
5. Lakukan dry-run satu file import dengan `dry_run=1`; pastikan tidak ada jumlah record yang berubah.
6. Verifikasi portal mahasiswa/dosen belum melihat periode yang hanya aktif internal.
7. Publikasikan hanya setelah seluruh kegagalan wajib nol dan approver memberi persetujuan.
8. Periksa notifikasi publikasi dan `academic_workflow_audits` tanpa membuka metadata pembayaran sensitif.
9. Aktifkan kembali worker, lalu `php artisan up`.

## 6. Rollback non-destruktif

Jika masalah terjadi sebelum publikasi dan migration terakhir belum menghasilkan data workflow, rollback satu migration dapat dipertimbangkan setelah backup tambahan:

```bash
php artisan migrate:rollback --step=1
```

Jika periode sudah dipublikasikan atau tabel audit/snapshot sudah berisi data, jangan menurunkan migration. Lakukan rollback aplikasi:

1. Masuk maintenance mode dan hentikan worker.
2. Set `is_published = 0` pada periode bermasalah setelah persetujuan Web Administrator.
3. Deploy kembali release aplikasi sebelumnya yang kompatibel dengan schema baru.
4. Pertahankan tabel/kolom baru; jangan menghapus audit, pembayaran, KRS, nilai, atau presensi.
5. Bila perubahan data harus dikoreksi, gunakan migration korektif baru atau transaksi SQL yang telah diuji pada salinan database.
6. Jalankan query pasca-rollback dan dokumentasikan perbedaan.
7. Pulihkan backup hanya sebagai opsi terakhir melalui prosedur disaster recovery yang disetujui.

## 7. Kriteria selesai

- Migration berstatus `Ran` dan tidak ada batch yang tertinggal `running`.
- Audit integritas menunjukkan nol referensi yatim untuk relasi wajib.
- Total historis per periode sama dengan hasil pra-deployment kecuali perubahan yang disetujui.
- Readiness snapshot tersimpan dengan nol kegagalan sebelum publikasi.
- Portal mahasiswa/dosen hanya membaca periode aktif yang dipublikasikan.
- Operator, Academic, Finance, Web Administrator, dan approver menandatangani catatan deployment.
