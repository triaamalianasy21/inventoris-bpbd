Manual test: Peminjaman (Pemohon)

1) Prasyarat
- Pastikan migrasi sebelumnya telah dijalankan (kolom `status`, `requested_by`, `verified_by`, `verified_at` pada tabel `peminjaman` tersedia).
- Pastikan ada satu akun user (role `pemohon`) dan satu atau lebih akun admin/pengurus.

2) Langkah pengujian cepat
- Login sebagai pemohon.
- Buka: `index.php?page=peminjaman` → seharusnya menampilkan daftar (kosong jika belum ada).
- Klik `+ Tambah Peminjaman` → isi `Keterangan` dan submit.
- Setelah submit, seharusnya muncul entri baru dengan `status = pending`.
- Login sebagai admin/pengurus → buka `index.php?page=peminjaman` → klik `Setujui` atau `Tolak` pada baris yang sesuai.
- Pastikan notifikasi dikirim ke pemohon (cek tabel `notifications` atau menu notifikasi di UI).

3) Catatan teknis
- Pemeriksaan single-active-loan: user tidak bisa mengajukan peminjaman baru jika masih ada peminjaman dengan status selain `completed` atau `rejected`.
- Pakta integritas: jika tabel `pakta_integritas` ada, pemohon harus memiliki entri terakhir `status='filled'` untuk bisa mengajukan.

4) Jika ada error
- Salin pesan error dan kirim di sini; saya akan bantu perbaiki.
