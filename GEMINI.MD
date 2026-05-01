# Project Context: Makassar Ujian (Laravel 13 Multi-Tenant)

## 🎯 Project Vision

Platform SaaS ujian sekolah berbasis multi-tenant (single database) yang aman, scalable, dan real-time. Melayani berbagai sekolah (tenants) dengan isolasi data yang ketat.

## 🏗️ Core Architecture Principles (WAJIB DIPATUHI)

Semua bantuan kode harus mengikuti aturan berikut:

### 1. Data Isolation (Tenant Scope)

- **Aturan**: Jangan pernah melakukan query tanpa filter `tenant_id`.
- **Implementasi**: Gunakan trait `App\Modules\Tenant\Traits\BelongsToTenant` pada setiap model transaksional (Attempt, Question, Exam, dll).
- **Security**: Pastikan middleware `IdentifyTenant` selalu aktif di rute yang membutuhkan isolasi.

### 2. Exam Engine & Performance

- **Write Strategy**: Gunakan Redis-first untuk autosave jawaban siswa. Jangan melakukan write langsung ke Database (PostgreSQL) setiap detik.
- **Scaling**: Gunakan sharded Redis counters untuk metrik real-time (jumlah peserta aktif, selesai, dll) di dashboard proktor.
- **Idempotency**: Semua proses submit ujian dan pembayaran harus dilindungi oleh `DistributedConsistencyGuard` (Redis-based lock) untuk mencegah double-submit.

### 3. Database & Model Structure

- **JSON Content**: Soal (Questions) disimpan dalam kolom JSON `content`. Gunakan accessor `$question->correctAnswer()` untuk mengambil kunci jawaban, jangan mencari kolom fisik `correct_option`.
- **Normalized Answers**: Jawaban siswa disimpan per-baris di tabel `attempt_answers`, bukan dalam blob JSON di tabel `attempts`.
- **UUID**: Gunakan UUID untuk semua ID publik yang terlihat oleh pengguna.

### 4. Frontend & Interaction

- **Stack**: Laravel Blade + Alpine.js. (Jangan gunakan Vue kecuali pada komponen spesifik yang sudah ada seperti QuestionForm).
- **Real-time**: Gunakan Laravel Reverb untuk event proctoring (CheatDetected, AnswerUpdated).
- **UI Consistency**: Ikuti standar Tailwind CSS 4 yang sudah terpasang.

## ⚠️ Forbidden Practices (JANGAN DILAKUKAN)

1. **NO Hardcoded Dummy Data**: Jangan menyarankan penambahan data statis di dalam file Blade. Gunakan variabel dari controller.
2. **NO Cross-Tenant Leak**: Dilarang memberikan saran query yang mem-bypass Global Scope `tenant_id`.
3. **NO Direct DB for Monitoring**: Jangan query Database PostgreSQL untuk statistik real-time yang frekuensinya tinggi; gunakan Redis dashboard cache.
4. **NO Manual ID**: Jangan gunakan ID integer urut (1, 2, 3) di URL; selalu gunakan UUID.

## 🛠️ Current Development Focus (Priority)

1. **Fixing ScoreCalculator**: Memperbaiki logika skoring agar membaca dari JSON `content` soal.
2. **Exam Controller**: Membangun CRUD untuk manajemen paket soal oleh Admin.
3. **Real-time Anti-Cheat**: Mengaktifkan sensor `visibilitychange` di JS untuk menembak API `cheat-log`.
