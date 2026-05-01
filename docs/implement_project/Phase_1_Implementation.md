# Implementation Plan: Phase 1 (Fix Critical Blocker)

Fase ini berfokus pada penyelesaian bug fatal dan celah keamanan data (tenant leak) yang menghambat jalannya flow ujian inti.

## User Review Required

> [!WARNING]
> **Penghapusan Model (Dead Code)**
> Saya akan secara permanen menghapus model `Result.php`, `Option.php`, dan `Answer.php` beserta isinya karena sudah digantikan oleh pendekatan *JSON/Snapshot* yang baru. Apakah Anda setuju file-file ini dihapus?

## Proposed Changes

---

### 1. Perbaikan Skoring (ScoreCalculator)

#### [MODIFY] [ScoreCalculator.php](file:///d:/Project/makassarujian/app/Services/ScoreCalculator.php)
- **Tujuan**: Memperbaiki fatal error saat siswa melakukan Submit Ujian (Crash 500).
- **Perubahan**: Mengubah pencarian jawaban yang benar dari `$question->correct_option` (yang tidak ada di tabel) menjadi membaca data JSON.
- **Logika Baru**: 
  ```php
  $correctOptionObj = $question->correctAnswer();
  $correct = $correctOptionObj['key'] ?? null;
  ```

#### [MODIFY] [Question.php](file:///d:/Project/makassarujian/app/Models/Question.php)
- **Perubahan**: Menambahkan atau menyempurnakan method pembantu untuk mengambil jawaban benar agar mudah dipanggil oleh `ScoreCalculator`.

---

### 2. Perbaikan Route Naming API

#### [MODIFY] [api.php](file:///d:/Project/makassarujian/routes/api.php)
- **Tujuan**: Menghindari *Route Not Found* di halaman Vue/Blade `pengerjaan.blade.php`.
- **Perubahan**: Menambahkan nama spesifik pada *route* ujian:
  - `->name('api.exam.save-answer')`
  - `->name('api.exam.session')`

---

### 3. Tenant Isolation Fix (Kritis - Keamanan Data)

#### [MODIFY] [Attempt.php](file:///d:/Project/makassarujian/app/Models/Attempt.php)
#### [MODIFY] [AttemptAnswer.php](file:///d:/Project/makassarujian/app/Models/AttemptAnswer.php)
#### [MODIFY] [AuditLog.php](file:///d:/Project/makassarujian/app/Models/AuditLog.php)
#### [MODIFY] [ExamSession.php](file:///d:/Project/makassarujian/app/Models/ExamSession.php)
- **Tujuan**: Mencegah kebocoran data (*data leak*) antar sekolah.
- **Perubahan**: 
  - Mengimpor trait `use App\Traits\BelongsToTenant;` 
  - Menambahkan baris `use BelongsToTenant;` ke dalam deklarasi *class* di atas. Dengan ini, semua query akan otomatis difilter berdasarkan `tenant_id` dari *user* yang sedang aktif.

---

### 4. Cleanup Dead Code

#### [DELETE] [Result.php](file:///d:/Project/makassarujian/app/Models/Result.php)
#### [DELETE] [Option.php](file:///d:/Project/makassarujian/app/Models/Option.php)
#### [DELETE] [Answer.php](file:///d:/Project/makassarujian/app/Models/Answer.php)
- **Tujuan**: Membersihkan arsitektur sistem dari *model* lama yang sudah tidak dipakai (digantikan oleh struktur *JSON/Normalized* baru).

## Verification Plan

### Automated/Manual Tests
- **Simulasi Ujian**: Mengakses *route* Submit Ujian dan memastikan API me-return JSON skor dengan sukses tanpa HTTP 500.
- **Tenant Scope Check**: Melakukan query `Attempt::all()` via *tinker* untuk memastikan *SQL output*-nya otomatis menambahkan `where tenant_id = ?`.
- **Dead Code Check**: Memastikan tidak ada *class* yang masih memakai *namespace* model-model yang telah dihapus.
