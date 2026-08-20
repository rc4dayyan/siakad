# Kontrak Legacy Periode Akademik

Dalam domain aplikasi, setiap record Ganjil, Genap, atau Semester Pendek adalah **Periode Akademik**. Tahun Akademik disimpan terpisah sebagai induknya. Nama fisik berikut dipakai untuk menjaga kompatibilitas data dan integrasi lama:

- tabel `tahun_akademik` menyimpan Tahun Akademik yang sebenarnya;
- tabel `tahun_akademiks` menyimpan record Periode Akademik;
- kolom `tahun_akademiks.tid` menghubungkan periode ke `tahun_akademik.id`;
- kolom `taka_id` adalah foreign key Periode Akademik;
- model `TahunAkademik` dan relasi `taka()` merupakan alias legacy.

Kode baru menggunakan `PeriodeAkademik`, variabel `$period`/`$academicPeriod`, dan relasi `periodeAkademik()`. Jangan menambahkan kolom `periode_akademik_id` berdampingan dengan `taka_id` karena keduanya akan mewakili konsep yang sama.

Header import/export `Kode Tahun Akademik` juga masih dipertahankan sebagai kontrak file legacy. Nilainya adalah kode Periode Akademik yang sedang dipilih.
