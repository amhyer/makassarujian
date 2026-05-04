# 📋 SOP UJIAN ONLINE — Makassar Ujian Platform

> **Versi:** 1.0 | **Berlaku mulai:** Mei 2026  
> Dokumen ini wajib dibaca oleh semua Admin Sekolah dan Proktor sebelum memulai sesi ujian.

---

## BAGIAN 1 — SEBELUM UJIAN (H-1 hingga H-30 menit)

### ✅ Checklist Admin Sekolah

| No | Tugas | Catatan |
|----|-------|---------|
| 1 | Buat paket soal dan atur durasi ujian | Pastikan `start_at` dan `end_at` sudah benar |
| 2 | Distribusikan ujian ke peserta (via halaman Distribusi) | Cek jumlah peserta terdaftar |
| 3 | Bagikan **Invite Code** ke siswa yang belum punya akun | Kode invite bisa dinonaktifkan setelah deadline |
| 4 | Verifikasi status server di `/monitoring/status-server` | Semua indikator harus **hijau** |
| 5 | Beritahu proktor tentang jadwal dan nomor chat darurat | — |
| 6 | Uji coba login dengan 1 akun siswa dummy | Pastikan alur login → dashboard → ujian berjalan |

### ✅ Checklist Proktor (H-30 menit)

- [ ] Buka halaman Monitoring Ujian Berlangsung
- [ ] Pastikan koneksi internet stabil (minimal 10 Mbps, disarankan kabel)
- [ ] Siapkan nomor HP admin dan nomor darurat IT sebagai backup
- [ ] Pastikan Anda sudah login sebagai School Admin atau Proktor
- [ ] Buka tab Telegram/WA untuk menerima notifikasi alert

---

## BAGIAN 2 — SAAT UJIAN BERLANGSUNG

### Tugas Proktor

1. **Pantau dashboard real-time** - Cek jumlah siswa aktif, selesai, dan offline.
2. **Perhatikan notifikasi anti-cheat** - Jika `focus_loss_count > 3` muncul di dashboard, catat NIS siswa untuk dilaporkan.
3. **Jangan ganggu siswa** - Biarkan sistem berjalan otomatis. Intervensi manual hanya jika ada permintaan eksplisit.

### Sistem Otomatis (Tidak Perlu Tindakan Manual)

| Kondisi | Respon Sistem |
|---------|---------------|
| Siswa tutup tab | Anti-cheat mencatat focus_loss, proktor mendapat notifikasi |
| Laptop siswa mati | Jawaban tersimpan di Redis. Saat online lagi, otomatis tersinkron |
| Waktu habis | Backend AutoSubmitExpiredAttempts akan submit otomatis dalam 1 menit |
| Redis lambat | SafeModeAnswerService aktif otomatis, data disimpan ke DB langsung |

### Indikator yang Perlu Diwaspadai (Dashboard Proktor)

| Indikator | Normal | Perlu Tindakan |
|-----------|--------|----------------|
| Peserta Aktif | >= 90% dari total | < 70% dalam 10 menit pertama |
| Queue Delay | < 1 detik | > 5 detik hubungi IT |
| Redis Sync Lag | < 30 detik | > 2 menit aktifkan Panic Mode |
| WS Connection | > 95% terhubung | < 80% kemungkinan masalah internet sekolah |

---

## BAGIAN 3 — PENANGANAN KONDISI DARURAT

### SKENARIO A: Siswa Tidak Bisa Login

**Gejala:** Siswa lapor tidak bisa masuk ke sistem.

**Langkah:**
1. Cek apakah siswa sudah punya akun - minta mereka cek email undangan.
2. Jika belum punya akun, berikan Invite Code dan minta daftar ulang.
3. Jika lupa password, arahkan ke halaman Lupa Password.
4. Jika akun terkunci, hubungi Admin Sekolah untuk reset.

> JANGAN buat akun baru dengan email berbeda - data tidak akan terhubung ke ujian.

---

### SKENARIO B: Siswa Terputus dari Internet

**Gejala:** Siswa offline di dashboard proktor.

**Langkah:**
1. **Tenangkan siswa** - jawaban mereka sudah tersimpan di perangkat (IndexedDB) dan Redis.
2. Minta siswa reconnect ke Wi-Fi atau gunakan data seluler.
3. Setelah online, sistem akan **otomatis sinkronisasi** jawaban yang tertunda.
4. Jika waktu tersisa < 5 menit dan siswa masih offline, sistem akan submit otomatis saat waktu habis.

> Tidak ada data yang hilang. Sistem memiliki 3 layer backup: IndexedDB - Redis - Database.

---

### SKENARIO C: Listrik Sekolah Padam / Koneksi Sekolah Putus (AKTIFKAN PANIC MODE)

**Gejala:** Ratusan siswa sekaligus offline, grafik koneksi WS turun drastis.

**Langkah:**
1. Aktifkan Panic Mode dari dashboard proktor via tombol "Panic Mode" di halaman Monitoring.
2. Tulis pesan untuk siswa, misalnya: "Ujian dijeda sementara, harap tunggu".
3. Semua siswa yang masih online akan melihat overlay peringatan otomatis.
4. Semua sesi ujian dijeda (status = paused).
5. Selesaikan masalah infrastruktur.
6. Setelah masalah selesai, klik "Nonaktifkan Panic Mode" - semua sesi dilanjutkan.

> PENTING: Jangan aktifkan Panic Mode tanpa alasan jelas - semua ujian akan dijeda serentak.

---

### SKENARIO D: Sistem Sangat Lambat / Error 500

**Langkah:**
1. Cek halaman Monitoring - Status Server, identifikasi komponen mana yang merah.
2. Jika Redis bermasalah: Sistem sudah otomatis beralih ke SafeMode - siswa bisa lanjut.
3. Jika Queue bermasalah: Jawaban mungkin delayed tapi tidak hilang. Hubungi IT.
4. Jika Database bermasalah: Ini darurat level tertinggi - hubungi IT segera.
5. JANGAN matikan server tanpa konfirmasi IT.

---

### SKENARIO E: Admin Salah Klik Distribusi

**Langkah:**
1. Buka halaman Ujian Distribusi.
2. Temukan entri yang salah.
3. Hapus distribusi ujian tersebut sebelum siswa memulai (jika belum ada attempt).
4. Jika siswa sudah mulai, hubungi Admin untuk membatalkan attempt secara manual (hanya IT yang bisa).

> Attempt yang sudah dimulai tidak bisa dihapus dari UI - harus via console artisan.

---

## BAGIAN 4 — SETELAH UJIAN

| No | Tugas | Siapa |
|----|-------|-------|
| 1 | Unduh laporan hasil ujian (Score per siswa) | Admin Sekolah |
| 2 | Cek siswa yang status-nya masih ongoing | Proktor |
| 3 | Laporkan siswa dengan focus_loss_count > 5 ke kepala sekolah | Proktor |
| 4 | Arsipkan data ujian (export CSV) | Admin Sekolah |
| 5 | Hapus Invite Code yang sudah tidak diperlukan | Admin Sekolah |

---

## KONTAK DARURAT

| Peran | Kontak | Kapan Dihubungi |
|-------|--------|-----------------|
| IT Support Platform | [ISI] | Error 500, Redis down, sistem tidak responsif |
| Admin Sekolah | [ISI] | Masalah akun siswa, distribusi soal |
| Vendor Internet Sekolah | [ISI] | Koneksi sekolah putus |

---

*Dokumen ini dibuat oleh tim teknis Makassar Ujian. Untuk update SOP, hubungi tim IT.*
