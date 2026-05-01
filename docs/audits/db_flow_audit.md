# Laporan Audit Database & Data Flow
> Fokus: Integritas Data, Multi-Tenant Isolation, dan Arsitektur Schema

Audit ini berfokus pada struktur tabel, relasi model Eloquent, dan konsistensi aliran data di dalam database untuk memastikan bahwa sistem beroperasi secara aman di lingkungan *multi-tenant*.

---

## 🚨 1. Temuan Kritis: Data Leak Antar Tenant (Cross-Tenant Leakage)

Arsitektur SaaS multi-tenant bergantung pada *Global Scope* untuk mengisolasi data tiap tenant secara otomatis. Trait `BelongsToTenant` telah diterapkan pada master data (User, Subject, Question, Exam), **TETAPI TERLEWAT** pada data transaksional ujian.

**Tabel/Model yang Terdampak:**
*   `Attempt.php`
*   `ExamSession.php`
*   `AttemptAnswer.php`
*   `AuditLog.php`

**Dampak Risiko (Tinggi):**
Meskipun tabel-tabel tersebut memiliki kolom `tenant_id` (ditambahkan melalui migrasi pada 27 April), ketiadaan trait `BelongsToTenant` membuat query Eloquent biasa (seperti `Attempt::all()`) akan mengembalikan **seluruh data dari semua sekolah**. Ini berpotensi sangat besar membocorkan hasil ujian dan jejak audit antar-sekolah (tenant) ke pengguna yang tidak berhak.

**Rekomendasi Perbaikan:**
Tambahkan `use App\Modules\Tenant\Traits\BelongsToTenant;` ke dalam keempat model di atas sesegera mungkin.

---

## 🗑️ 2. Ghost Models & Tabel Tidak Terpakai

Terdapat sisa-sisa arsitektur lama (dead code) di folder `app/Models/` yang sudah tidak memiliki tabel fisik atau tidak lagi digunakan dalam alur sistem:

1.  `Option.php`: Tabel `options` sudah di-drop oleh migrasi `2026_04_27_044543_drop_options_table.php`. Model ini harus **dihapus**.
2.  `Result.php`: Skor ujian disimpan langsung ke tabel `attempts` (`score` dan `result_snapshot`). Model dan tabel `results` **tidak pernah terpakai** dan harus didrop.
3.  `Answer.php` & `AttemptQuestion.php`: Skema lama. Alur saat ini telah digantikan secara penuh oleh `AttemptAnswer.php` dan tabel `attempt_answers` yang ternormalisasi. Model lama ini harus **dihapus**.

---

## 🔄 3. Inkonsistensi Kolom JSON & Source of Truth

**A. Skema `Question` (JSON Mismatch)**
Data soal (teks dan opsi) disimpan sebagai JSON blob di dalam kolom `content`. Hal ini menyebabkan inkonsistensi pada *ScoreCalculator* yang justru mencari kolom flat `correct_option` (menyebabkan bug fatal seperti yang ditemukan pada audit engine sebelumnya).

**B. Ambigu Source of Truth pada `Attempt->answers`**
Di dalam model `Attempt.php`, ada metode fallback:
```php
public function getAnswersAttribute(): array
```
Metode ini mencoba menarik data dari relasi `AttemptAnswer` (tabel ternormalisasi). Namun, jika gagal, ia akan mencoba membaca kolom fisik `answers` (JSON lama) di tabel `attempts`.
*Masalah:* Menyimpan data yang sama di dua tempat (Relasi SQL vs Blob JSON) sangat berisiko terhadap *split-brain* (satu sumber di-update, sumber lainnya tidak). Karena migrasi ke tabel ternormalisasi `attempt_answers` sudah rampung (28 April), kolom JSON `answers` sebaiknya di-*drop* sepenuhnya dari tabel `attempts`.

---

## 🔗 4. Relasi Foreign Key
Secara umum, Foreign Key dan indeks performansi sangat baik. Migrasi `2026_04_28_000001_add_optimal_indexes_to_attempts_table.php` dan kawan-kawannya telah mengunci *referential integrity* menggunakan `cascadeOnDelete()`. 

Satu-satunya gap relasi (eloquent) adalah tidak adanya fungsi deklaratif yang mengubungkan secara jelas antara model transaksional dengan `Tenant::class` (meskipun foreign id ada di DB).

---

## 🛠️ Ringkasan Rekomendasi Eksekusi

1.  **[High Priority]** Inject `BelongsToTenant` trait ke dalam `Attempt`, `AttemptAnswer`, `ExamSession`, dan `AuditLog` untuk menyumbat celah data leak.
2.  **[Medium Priority]** Hapus `Option.php`, `Result.php`, `Answer.php`, dan `AttemptQuestion.php` untuk membersihkan *technical debt*.
3.  **[Medium Priority]** Buat migration baru untuk mendrop kolom `answers` (JSON) dari tabel `attempts` agar aplikasi 100% bersandar pada tabel *attempt_answers*.
