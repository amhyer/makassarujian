# Implementation Plan: Phase 3 (Distribusi & User Flow)

Fase ini akan menghubungkan proses dari sudut pandang alur pengguna (End-to-End). Kita akan memastikan Admin bisa membagikan ujian, dan Siswa bisa melihat serta mulai mengerjakannya dari Dasbor mereka.

## User Review Required

> [!WARNING]
> **Metode Distribusi Ujian**
> Untuk saat ini, saya akan membuat fungsi distribusi ujian berbasis *Per Siswa* (assign berdasarkan `user_id`). Ke depannya ini bisa di-*upgrade* menjadi berbasis *Kelas* (assign ke `class_id`). Apakah Anda setuju dengan pendekatan awal ini untuk memastikan flow E2E berjalan?

## Proposed Changes

---

### 1. Manajemen Distribusi Ujian (Admin)

#### [MODIFY] [ExamParticipant.php](file:///d:/Project/makassarujian/app/Models/ExamParticipant.php)
- **Perubahan**: Menambahkan relasi ke `User` dan `Exam`, serta memasang trait `BelongsToTenant` agar data distribusi tidak bercampur antar sekolah.

#### [NEW] [DistributionController.php](file:///d:/Project/makassarujian/app/Http/Controllers/DistributionController.php)
- **Tujuan**: Endpoint API / Controller untuk Admin saat menekan tombol "Bagikan Ujian".
- **Logika**: Menerima `exam_id` dan *array* `user_ids`, lalu menyimpannya ke tabel `exam_participants`.

---

### 2. Memperbaiki Dasbor Siswa & Metrics

#### [MODIFY] [DashboardService.php](file:///d:/Project/makassarujian/app/Services/DashboardService.php)
- **Perubahan**: Menambahkan fungsi `getStudentMetrics($tenantId, $userId)` yang hilang. Fungsi ini bertugas mencari ujian mana saja yang sudah di-assign ke siswa tersebut.

#### [MODIFY] [siswa.blade.php](file:///d:/Project/makassarujian/resources/views/dashboard/siswa.blade.php)
- **Perubahan**: Mengubah tampilan statis "Ujian Tersedia" menjadi dinamis (me-render `metrics['available_exams']`). Setiap *card* ujian akan memiliki tombol "Mulai Kerjakan".

---

### 3. Logika Mulai Ujian (Join Exam Flow)

#### [MODIFY] [StudentExamController.php](file:///d:/Project/makassarujian/app/Http/Controllers/StudentExamController.php)
- **Perubahan**: Menambahkan *method* `join(Exam $exam)` yang berfungsi saat siswa menekan "Mulai".
- **Logika**: 
  1. Cek apakah siswa berhak ikut (ada di `exam_participants`).
  2. Buat *record* `Attempt` baru di *database* dengan `status = ongoing`.
  3. Redirect siswa ke halaman `/ujian/{attempt}/lobby` (yang sudah kita buat di Phase 2).

#### [MODIFY] [web.php](file:///d:/Project/makassarujian/routes/web.php)
- Mendaftarkan *route* baru:
  ```php
  Route::post('/{exam}/join', [\App\Http\Controllers\StudentExamController::class, 'join'])->name('ujian.join');
  ```

## Verification Plan

### Automated/Manual Tests
- **Sisi Admin**: Mengirim (*assign*) sebuah `Exam` ke satu atau beberapa akun Siswa percobaan.
- **Sisi Siswa**: *Login* menggunakan akun Siswa, melihat Dasbor, memastikan kartu ujian muncul. Menekan tombol "Mulai", dan memastikan sistem mengalihkan ke lobi ujian tanpa *error*.
