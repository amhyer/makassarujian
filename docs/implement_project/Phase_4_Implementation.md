# Implementation Plan: Phase 4 (UI Integration & Polishing)

Fase ini akan memoles Antarmuka Pengguna (UI) dan Pengalaman Pengguna (UX) terutama di sisi Siswa. Tujuan utamanya adalah memastikan transisi antar halaman (Dasbor ➔ Lobi ➔ Ujian ➔ Dasbor) berjalan rapi dengan umpan balik visual yang berkelas (*SaaS-grade*).

## User Review Required

> [!TIP]
> **Alur UX Lobi Ujian**
> Saat ini, alur yang dirancang adalah: Dasbor ➔ Lobi Ujian (Menampilkan Peraturan & Detail Waktu) ➔ Pengerjaan Ujian. Setelah menekan "Submit" di akhir ujian, siswa akan diarahkan kembali ke Dasbor dengan notifikasi berhasil. Apakah Anda setuju dengan *flow* dan halaman khusus Lobi ini?

## Proposed Changes

---

### 1. Membangun Halaman Lobi Ujian (Ruang Persiapan)

#### [NEW] [lobby.blade.php](file:///d:/Project/makassarujian/resources/views/pages/ujian/lobby.blade.php)
- **Tujuan**: Halaman transit antara Dasbor dan Pengerjaan Ujian.
- **Isi Konten**:
  - Informasi Ujian: Nama Mata Pelajaran, Judul Ujian, Durasi Waktu, dan Total Soal.
  - Peraturan Ujian: Penjelasan ringkas larangan (jangan menutup *browser*, dsb).
  - Tombol "Mulai Kerjakan" yang terhubung ke *route* `ujian.kerjakan`.

---

### 2. Polishing UI Pengerjaan Ujian & Feedback Submit

#### [MODIFY] [pengerjaan.blade.php](file:///d:/Project/makassarujian/resources/views/pages/ujian/pengerjaan.blade.php)
- **Tujuan**: Memperhalus pengalaman pengguna (UX) saat selesai ujian.
- **Perubahan**:
  - Mengubah *native* `alert()` atau `confirm()` menjadi antarmuka UI interaktif (menambahkan `isSubmitting` di state AlpineJS untuk mengubah tombol menjadi *loading state*).
  - Menggunakan animasi sederhana sebelum melakukan pengalihan (*redirect*) ke Dasbor.

---

### 3. Flash Messages di Dasbor

#### [MODIFY] [siswa.blade.php](file:///d:/Project/makassarujian/resources/views/dashboard/siswa.blade.php)
- **Tujuan**: Menampilkan pesan sukses kepada siswa setelah ujian selesai disubmit.
- **Perubahan**:
  - Menangkap `session('info')` atau `session('success')` dan merendernya dalam bentuk *toast* atau *alert box* sementara.

## Verification Plan

### Manual Verification
1. Lakukan *login* sebagai siswa.
2. Klik "Mulai Ujian" di Dasbor (akan mengarah ke Lobi).
3. Di Lobi, periksa estetika informasi dan aturan ujian, pastikan tombol "Mulai Kerjakan" berfungsi.
4. Selesaikan ujian (klik tombol Selesai), periksa tombol berganti menjadi indikator *loading*.
5. Verifikasi bahwa setelah sukses menyimpan skor, siswa dilempar ke Dasbor dan menerima notifikasi "Ujian Telah Selesai".
