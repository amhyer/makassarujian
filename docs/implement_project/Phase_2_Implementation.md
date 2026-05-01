# Implementation Plan: Phase 2 (Hidupkan Core Exam Engine)

Fase ini bertujuan untuk menghubungkan sirkuit utama dari aplikasi ujian. Setelah fase ini selesai, Admin bisa membuat ujian sungguhan, memilih soal, dan Siswa bisa mengerjakan soal asli dari database hingga disubmit (tanpa *crash*).

## User Review Required

> [!WARNING]
> **Rute Halaman Pengerjaan Belum Ada**
> Dari penelusuran, ternyata file `pengerjaan.blade.php` saat ini "melayang" (tidak punya URL / Controller yang memanggilnya). Saya akan membuat *route* baru `GET /ujian/{attempt}/kerjakan` khusus untuk Siswa agar mereka bisa mengakses halaman ujian tersebut.

## Proposed Changes

---

### 1. Build ExamController (Manajemen Ujian oleh Admin)

#### [NEW] [ExamController.php](file:///d:/Project/makassarujian/app/Http/Controllers/ExamController.php)
- **Tujuan**: Membuat *Controller* standar (CRUD) agar Admin bisa mengelola sesi ujian.
- **Fitur Utama**:
  - `index()`: Menampilkan daftar ujian yang dibuat.
  - `create()` & `store()`: Form dan logika menyimpan paket ujian baru (termasuk durasi, pengacakan soal, dan jadwal).
  - `edit()` & `update()`: Mengubah pengaturan ujian.

#### [MODIFY] [web.php](file:///d:/Project/makassarujian/routes/web.php)
- Mendaftarkan *resource route* untuk `ExamController`:
  ```php
  Route::resource('exams', \App\Http\Controllers\ExamController::class);
  ```

---

### 2. Relasi Exam ↔ Question

#### [MODIFY] [ExamController.php](file:///d:/Project/makassarujian/app/Http/Controllers/ExamController.php)
- **Tujuan**: Menghubungkan paket soal ke dalam Ujian.
- **Logika**: Di dalam `update()` atau via endpoint khusus, admin akan memilih *Question ID* untuk di-attach ke tabel pivot `exam_questions`. Kita akan memastikan fitur ini ada di *backend* agar Ujian tidak kosong.

---

### 3. Menghidupkan UI Pengerjaan Ujian (Siswa)

#### [NEW] [StudentExamController.php](file:///d:/Project/makassarujian/app/Http/Controllers/StudentExamController.php)
- **Tujuan**: Mengatur "Lobby" ujian dan merender halaman soal bagi siswa.
- **Fitur**:
  - `kerjakan(Attempt $attempt)`: Me-render file `pengerjaan.blade.php`.

#### [MODIFY] [pengerjaan.blade.php](file:///d:/Project/makassarujian/resources/views/pages/ujian/pengerjaan.blade.php)
- **Tujuan**: Membuang soal *dummy* ("Apa ibukota Indonesia?") dan menggantinya dengan injeksi soal dinamis.
- **Perubahan (Vue/Alpine Data)**:
  ```javascript
  questions: @json($attempt->exam->getQuestionsForExam()), // Memanggil fungsi randomisasi asli dari Model Exam
  ```

---

### 4. Fix Submit Flow

#### [MODIFY] [pengerjaan.blade.php](file:///d:/Project/makassarujian/resources/views/pages/ujian/pengerjaan.blade.php)
- **Tujuan**: Memberi respon sukses setelah siswa menekan "Submit".
- **Perubahan**: Saat fungsi `finishExam()` selesai menembak `/api/exam/submit`, akan me-*redirect* siswa keluar dari halaman ujian (misalnya diarahkan kembali ke `/siswa/dashboard` dengan notifikasi sukses).

## Verification Plan

### Automated/Manual Tests
- **Admin**: Buka `/exams/create`, buat ujian baru, dan pastikan ujian tersimpan di *database*.
- **Siswa**: Menuju URL `/ujian/{id}/kerjakan`, pastikan soal yang muncul adalah soal yang dimasukkan oleh Admin (bukan *dummy*), dan pastikan jawaban bisa di-*submit* serta nilai terekam utuh.
