# Implementation Plan: Phase 5 (Future SaaS Improvements)

Fase ini dieksekusi setelah alur inti aplikasi (Core Exam Engine) berjalan stabil. Tujuannya adalah meningkatkan skalabilitas (kinerja server) dan nilai jual platform SaaS (fitur analitik & monetisasi).

## 🔄 Alur Kerja (Workflow)
1. **Pilih Modul:** Fokus pada satu modul secara utuh (misal: Selesaikan WebSockets dulu sebelum pindah ke Billing).
2. **Kerjakan & Terapkan Perubahan:** Modifikasi *Controller*, *Model*, *Migration*, atau *View*.
3. **Verifikasi & Testing:** Pastikan fitur baru tidak mengganggu fungsionalitas ujian yang sudah berjalan (Regression Test).
4. **Checklist:** Tandai tugas menjadi selesai dengan mengubah `[ ]` menjadi `[x]`.

---

## 📋 WBS - Pembagian Tugas (Backlog)

### 🚀 Modul 1: Arsitektur Real-time (WebSockets)
*Target: Mengganti sistem HTTP Polling yang memberatkan server menjadi sistem Push real-time untuk pengawasan (Proctoring).*

- [ ] **Task 1.1: Setup Ekosistem WebSocket**
  - **Aksi:** Instal dan konfigurasi `laravel/reverb` (jika pakai Laravel 11) atau `pusher/soketi` beserta `laravel-echo` di frontend.
- [ ] **Task 1.2: Amankan Channels**
  - **Target File:** `routes/channels.php`
  - **Aksi:** Tulis otorisasi channel `exam-proctor.{examId}` agar hanya pengawas sah yang bisa terhubung.
- [ ] **Task 1.3: Refactor UI Pengawasan**
  - **Target File:** `resources/views/pages/ujian/pengawasan.blade.php` (atau file serupa)
  - **Aksi:** Ubah script JavaScript yang melakukan `setInterval(fetch)` menjadi *event listener* `Echo.private().listen(...)`.

### 🧠 Modul 2: Bank Soal Cerdas (Intelligent Question Bank)
*Target: Memberikan alat bagi guru untuk mengkategorikan dan mengevaluasi kualitas soal ujian.*

- [ ] **Task 2.1: Skema Tagging & Kategorisasi**
  - **Aksi:** Buat *migration* untuk tabel `tags` dan tabel pivot `question_tags`.
- [ ] **Task 2.2: Update UI Manajemen Soal**
  - **Target File:** `App\Http\Controllers\QuestionController.php`, UI Bank Soal.
  - **Aksi:** Tambahkan input *Multi-select* untuk menetapkan tingkat kesulitan, topik, dan bab mata pelajaran saat membuat soal.
- [ ] **Task 2.3: Statistik & Analitik Soal**
  - **Aksi:** Bangun query di *backend* untuk melacak: berapa kali soal dijawab benar/salah secara keseluruhan (bisa ditambahkan ke `ScoreCalculator`).

### 📊 Modul 3: Analitik & Pelaporan Nilai (Reporting)
*Target: Memberikan wawasan yang mendalam kepada pimpinan sekolah atau orang tua.*

- [ ] **Task 3.1: Pembuatan AnalyticsController**
  - **Target File:** `App\Http\Controllers\AnalyticsController.php`
  - **Aksi:** Buat endpoint untuk menghasilkan agregat nilai per kelas atau per mata pelajaran.
- [ ] **Task 3.2: Visualisasi Tren**
  - **Target File:** *View* Dasbor Sekolah / Guru.
  - **Aksi:** Gunakan `ApexCharts` (seperti di dasbor siswa) untuk menggambar grafik rata-rata kelas.
- [ ] **Task 3.3: Export Engine (PDF/Excel)**
  - **Aksi:** Instal `Maatwebsite/Laravel-Excel` dan `Barryvdh/Laravel-DomPDF`. Buat fitur "Unduh Rapor" atau "Unduh Rekap Nilai".

### 💳 Modul 4: Monetisasi & Sistem Billing
*Target: Mengotomatiskan tagihan dan pembatasan fitur sesuai paket berlangganan.*

- [x] **Task 4.1: Model Data Subscription**
  - **Aksi:** Buat migration untuk tabel `plans` (daftar paket, misal: Basic, Pro, Enterprise) dan `subscriptions` (langganan aktif per tenant).
- [x] **Task 4.2: Middleware Pembatas Kuota (Plan Limits)**
  - **Target File:** `App\Http\Middleware\CheckPlanLimits.php`
  - **Aksi:** Validasi apakah institusi sudah melebihi batas kuota siswa (`student_limit`) yang diizinkan oleh paket mereka.
- [x] **Task 4.3: UI Manajemen Paket Super Admin**
  - **Target File:** `App\Http\Controllers\Billing\PlanController.php`
  - **Aksi:** Selesaikan integrasi halaman agar Super Admin dapat membuat dan mengatur harga/limit paket secara dinamis.

---
*Catatan: Dokumen ini berfokus pada rencana perbaikan strategis pasca-MVP.*