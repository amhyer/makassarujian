# Implementation Plan: Phase 5 (Hardening Final)

Fase pamungkas ini akan memfokuskan pada **Keamanan Tingkat Lanjut (Security Hardening)** dan **Production Readiness**. Sistem SaaS harus kebal dari serangan siber sederhana maupun celah logika (Logic Flaw).

## User Review Required

> [!CAUTION]
> **Kerentanan IDOR Terdeteksi**
> Berdasarkan audit terakhir, sistem API (terutama `api.exam.submit` dan `api.exam.save-answer`) belum memvalidasi apakah `attempt_id` yang dikirim benar-benar milik pengguna yang sedang *login*. Hal ini memungkinkan seorang siswa yang "iseng" mengubah payload untuk menyabotase ujian siswa lain. **Fase ini wajib dieksekusi sebelum *Go-Live*.**

## Proposed Changes

---

### 1. Menambal Celah IDOR pada API Ujian

#### [MODIFY] [ExamSessionController.php](file:///d:/Project/makassarujian/app/Http/Controllers/Api/ExamSessionController.php)
- **Tujuan**: Memastikan operasi sensitif (menyimpan jawaban & mengumpulkan ujian) 100% aman.
- **Perubahan**:
  - Pada *method* `submit`: Setelah melakukan `Attempt::findOrFail($request->attempt_id)`, sistem akan memeriksa ulang menggunakan `abort_if($attempt->user_id !== Auth::id(), 403)` dan `abort_if($attempt->tenant_id !== Auth::user()->tenant_id, 403)`.
  - Pada *method* `saveAnswer`: Menambahkan logika otorisasi yang sama agar jawaban tidak masuk ke lembar ujian milik *user* lain.
  - Pada *method* `reportTabSwitch` dan `logCheat`: Menambahkan otorisasi kepemilikan `attempt_id` untuk menghindari injeksi *log* kecurangan palsu ke akun orang lain.

---

### 2. Finalisasi dan Pembersihan

#### [MODIFY] [task.md](file:///C:/Users/USER/.gemini/antigravity/brain/f4ebd5db-ba25-40af-a1d3-c875aaf9be7b/task.md)
- Membuat *checklist* final untuk memastikan tak ada kode mati (dead-code) yang mengganggu eksekusi API.
- Memastikan aplikasi siap untuk dirilis (Production-Ready).

## Verification Plan

### Manual Verification
1. Lakukan pengujian pengumpulan ujian secara *normal*.
2. Cobalah melakukan simulasi intersepsi API (menggunakan *developer tools* atau *Postman*) untuk mengirim *request* `save-answer` menggunakan `attempt_id` milik *user* lain. API harus dengan tegas menolak dan mengembalikan status **403 Forbidden**.
3. Pastikan tidak ada bentrok *Tenant* (sekolah A mengakses data sekolah B).
