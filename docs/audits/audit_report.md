# Laporan Audit — Makassar Ujian Platform
> Tanggal: 30 April 2026 | Versi kode aktif saat audit

---

## Ringkasan Eksekutif

**Kesimpulan:** Platform ini memiliki fondasi infrastruktur yang sangat solid (Redis, audit buffer, queue, anti-cheat), namun **lapisan fitur bisnis utama belum jalan end-to-end**. Core exam flow (create → distribute → run → hasil) tidak bisa dijalani user secara penuh karena terdapat 3 *critical blocker* yang memutus alur.

| Kategori | Jumlah Gap |
|---|---|
| 🔴 Critical | 7 |
| 🟡 Medium | 9 |
| 🟢 Low | 5 |

---

## 🔴 CRITICAL GAPS

### C-01: Tidak Ada Halaman untuk Membuat/Mengelola Ujian (ExamController)

**Dampak:** Admin sekolah tidak bisa membuat ujian. Flow utama platform tidak berjalan.

- **Tidak ada** `ExamController` sama sekali
- **Tidak ada** route untuk `GET /ujian/create`, `POST /ujian`, `GET /ujian/{id}/edit`
- Model `Exam` sudah ada, relasi ke `Question` sudah ada, tapi **tidak ada UI/controller yang menyentuhnya**
- Tidak ada halaman list ujian untuk School Admin

```
Pencarian: grep "ExamController" → 0 hasil
Pencarian: grep "Exam::create" di Controllers → 0 hasil
```

---

### C-02: Halaman Pengerjaan Ujian Menggunakan Soal Hardcoded

**Dampak:** Siswa yang membuka halaman ujian mendapat soal dummy, bukan soal dari DB.

Di `resources/views/pages/ujian/pengerjaan.blade.php` baris 112–115:
```javascript
questions: [
    { id: 1, text: 'Apa ibukota Indonesia?', options: { A: 'Jakarta', ... } },
    { id: 2, text: 'Siapa presiden pertama Indonesia?', ... },
],
```

- Variabel `$attempt` sudah ada (passed dari controller), tapi **soal tidak di-load dari `$attempt->exam->questions`**
- `api.exam.save-answer` dan `api.exam.session` route **tidak ada** di routes/api.php (route name tidak terdaftar)
- Tombol "Selesai Ujian" memanggil `document.getElementById('finish-form').submit()` tapi **tidak ada form `#finish-form`** di halaman

---

### C-03: Named Routes `api.exam.*` Tidak Terdaftar

**Dampak:** Halaman pengerjaan ujian akan error 500 saat di-render karena `route('api.exam.save-answer')` tidak ditemukan.

Routes di `api.php` menggunakan nama:
- `throttle:exam-api` ✓
- `throttle:exam-autosave` ✓

Tapi nama route yang dipakai di view:
- `route('api.exam.save-answer')` → **tidak ada** (tersedia: `/api/exam/save-answer` tapi tidak punya `->name()`)
- `route('api.exam.session')` → **tidak ada**

---

### C-04: Tidak Ada Halaman Hasil Ujian (Post-Submit)

**Dampak:** Setelah siswa submit, tidak ada halaman untuk melihat skor/pembahasan.

- `ScoreCalculator` sudah ada dan berfungsi dengan baik — nilai dihitung dan disimpan ke `result_snapshot`
- Tapi **tidak ada** controller/route/view untuk menampilkan hasil
- Tidak ada `GET /ujian/hasil/{attempt_id}` atau sejenisnya
- `Result` model ada di DB tapi **tidak pernah digunakan** di controller manapun

---

### C-05: Dashboard Siswa Menggunakan Data Dummy Hardcoded

**Dampak:** Metrics di dashboard siswa selalu statis — tidak mencerminkan data real.

Di `dashboard/siswa.blade.php`:
```javascript
data: [75, 82, 78, 88, 92, 85]  // baris 101 — data hardcoded
```
```html
<h4>Bahasa Indonesia - Bab 3</h4>   <!-- baris 62 — nama ujian dummy -->
<p>Guru: Bpk. Ahmad Yani</p>        <!-- baris 63 — guru dummy -->
<button>Mulai Ujian</button>        <!-- tombol tanpa link ke ujian nyata -->
```

`DashboardService::getStudentMetrics()` sudah ada dan return metrics real (`ujian_mendatang`, `ujian_selesai`, dll), tapi section "Ujian Hari Ini" **tidak menggunakan `$metrics`**, melainkan hardcoded HTML.

---

### C-06: Tidak Ada Mekanisme Distribusi Ujian ke Siswa

**Dampak:** Bahkan jika admin berhasil membuat ujian, tidak ada cara untuk mendistribusikannya ke siswa.

- Tabel `exam_participants` sudah ada di migration
- `ExamParticipant` model ada tapi **tidak dipakai di controller manapun**
- Halaman `/ujian/distribusi` hanya menampilkan "Data Contoh 1/2/3" hardcoded
- Tidak ada CRUD untuk `exam_participants`

---

### C-07: Self-Registration Tidak Fungsional

**Dampak:** Siswa tidak bisa mendaftar sendiri — hanya admin bisa dibuat via impersonation flow.

- Route `GET /register` → `Route::view('/register', 'auth.register')` tapi **tidak ada `POST /register`**
- Tidak ada `RegisterController`
- Siswa harus dibuat manual oleh admin, tapi tidak ada UI untuk "tambah siswa" di School Admin dashboard

---

## 🟡 MEDIUM GAPS

### M-01: Banyak Halaman Monitoring Masih Template Dummy

**Halaman-halaman berikut menampilkan "Data Contoh 1/2/3" hardcoded:**

| Halaman | Route | Status |
|---|---|---|
| Aktivitas Siswa | `/monitoring/aktivitas-siswa` | Dummy data + pagination palsu |
| Ujian Berlangsung | `/monitoring/ujian-berlangsung` | `sessions = collect([])` |
| Audit Log | `/sistem/audit-log` | `logs = collect([])` |
| Distribusi Soal | `/ujian/distribusi` | Dummy tabel |
| Template Ujian | `/ujian/template` | `templates = collect([])` |
| User Mgt. Admin Sekolah | `/user-management/admin-sekolah` | `admins = collect([])` |
| User Mgt. Admin FKGG | `/user-management/admin-fkgg` | `admins = collect([])` |
| Role & Permission | `/sistem/role-permission` | `roles = collect([])` |
| Support > Tiket | `/support/tiket` | `tickets = collect([])` |

Semua ini di-serve oleh `SuperAdminPageController` yang secara eksplisit mengirim `collect([])` dan nilai `0` untuk semua stats.

---

### M-02: Bank Soal Pakai Vue Component Tanpa Backend Jelas

`/ujian/bank-soal` menggunakan:
```html
<question-stats></question-stats>
<question-list></question-list>
```
Vue components, tapi `QuestionController@index` yang ada adalah controller web biasa yang return `view('questions.index')` — ada dua halaman berbeda untuk soal yang tidak terkoneksi satu sama lain.

---

### M-03: Status Server Monitoring Semua Hardcoded `'up'`

Di `SuperAdminPageController::statusServer()`:
```php
['name' => 'Web Server (Nginx)', 'status' => 'up', 'latency' => '—', 'uptime' => '—'],
['name' => 'Database (MySQL)',   'status' => 'up', 'latency' => '—', 'uptime' => '—'],
```
`SystemHealthController` sudah ada di API, tapi halaman monitoring server tidak menggunakannya.

---

### M-04: `exam.{examId}` Channel Tidak Validasi DB

Di `routes/channels.php`:
```php
Broadcast::channel('exam.{examId}', function ($user, $examId) {
    return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role];
});
```
Semua user yang terautentikasi **langsung diterima** tanpa cek apakah mereka peserta ujian tersebut. Tidak ada query ke `exam_participants`.

---

### M-05: `questions` Kolom `ilike` — Tidak Kompatibel MySQL

```php
$query->where('content->question_text', 'ilike', '%' . $request->search . '%');
```
`ilike` adalah operator PostgreSQL. Jika project jalan di MySQL, search soal akan error/gagal.

---

### M-06: `Result` Model/Tabel Tidak Pernah Digunakan

Tabel `results` ada (migration ada), model `Result.php` ada, tapi **tidak ada controller yang pernah write ke tabel ini**. Score disimpan langsung ke `attempts.result_snapshot`. Tabel `results` adalah dead code.

---

### M-07: Konfigurasi Platform Tidak Persist

`/sistem/konfigurasi` menampilkan settings (trial_days, max_participants, dll) tapi semua nilai adalah hardcoded di controller — tidak ada table `settings` / `configurations`, tidak ada `POST /sistem/konfigurasi`.

---

### M-08: `channels.php` Belum Menggunakan `BroadcastAuthCacheService`

`BroadcastAuthCacheService.php` sudah dibuat, tapi `channels.php` masih menggunakan callback kosong tanpa caching.

---

### M-09: Tidak Ada `tenant_id` Scope pada `QuestionController::index`

```php
$query = Question::with(['subject', 'creator']);
```
Tidak ada filter `where('tenant_id', Auth::user()->tenant_id)`. School Admin A bisa melihat soal School Admin B jika ada bug di middleware.

---

## 🟢 LOW GAPS

### L-01: Route `/register` View-Only

`auth/register.blade.php` ada tapi form tidak bisa disubmit (tidak ada POST handler). Akibatnya form register adalah halaman kosong yang tidak berguna.

---

### L-02: `forgot-password` dan `reset-password` Tidak Fungsional

Route ada tapi hanya `Route::view()` — tidak ada controller untuk handle POST.

---

### L-03: `create.blade.php` Kosong di Questions

`resources/views/questions/create.blade.php` hanya 229 bytes (hampir kosong), sedang `form.blade.php` sudah lengkap — duplikasi membingungkan.

---

### L-04: Proctor Controller Terbatas

`ProctorController::getStats()` ada, tapi tidak ada halaman proctor yang lengkap untuk monitoring real-time per-exam.

---

### L-05: Super Admin Dashboard Tidak Menggunakan `DashboardService`

```php
public function superAdmin() {
    return view('dashboard.index', ['title' => 'Super Admin Overview']);
}
```
Tidak ada data apapun yang dikirim — padahal `DashboardService` sudah punya method yang relevan.

---

## 📊 Data Flow Analysis

```
✅  Question CRUD         → DB (questions table) ← BERFUNGSI
✅  Attempt::start        → DB + Redis ← BERFUNGSI  
✅  SaveAnswer            → attempt_answers table ← BERFUNGSI
✅  Submit + Score        → result_snapshot di attempts ← BERFUNGSI
✅  Audit Buffer          → Redis → exam_audit_logs ← BERFUNGSI
❌  Exam::create/manage   → TIDAK ADA
❌  ExamParticipant CRUD  → TIDAK ADA
❌  Hasil page            → TIDAK ADA
❌  Register siswa        → TIDAK ADA
```

---

## 🔄 End-to-End Flow Check

| Step | Status | Keterangan |
|---|---|---|
| 1. Register siswa | ❌ | Tidak ada POST handler |
| 2. Login | ✅ | Berfungsi, role-based redirect OK |
| 3. Admin buat ujian | ❌ | ExamController tidak ada |
| 4. Admin tambah soal ke ujian | ❌ | Tidak ada UI untuk `exam_questions` pivot |
| 5. Admin distribusi ke siswa | ❌ | ExamParticipant tidak ada di UI |
| 6. Siswa lihat daftar ujian | ❌ | Dashboard dummy |
| 7. Siswa mulai ujian | ⚠️ | API `/exam/start` ada, tapi view soal dummy |
| 8. Siswa kerjakan soal | ⚠️ | SaveAnswer berfungsi, tapi soal hardcoded |
| 9. Siswa submit | ✅ | Score dihitung dan disimpan |
| 10. Siswa lihat hasil | ❌ | Tidak ada halaman hasil |

**Hanya 2 dari 10 step yang fully berfungsi end-to-end.**

---

## 🎯 Prioritas Perbaikan

### Sprint 1 — Core Business Flow (Blocker)

1. **[C-03]** Tambahkan nama route ke API routes (`->name('api.exam.save-answer')` dll)
2. **[C-01]** Buat `ExamController` dengan CRUD lengkap + halaman list + create + edit
3. **[C-02]** Hubungkan soal dari DB ke halaman pengerjaan (hapus hardcoded questions)
4. **[C-04]** Buat halaman hasil ujian `/ujian/hasil/{attemptId}`
5. **[C-07]** Tambah route + controller untuk registrasi siswa oleh admin

### Sprint 2 — Distribusi & Monitoring

6. **[C-06]** Buat UI distribusi ujian ke siswa (ExamParticipant CRUD)
7. **[C-05]** Fix dashboard siswa — gunakan data dari DB bukan dummy
8. **[M-04]** Fix channel auth — validasi `exam_participants`
9. **[M-08]** Integrasikan `BroadcastAuthCacheService` ke `channels.php`

### Sprint 3 — Polishing & Operations

10. **[M-01]** Isi halaman monitoring dengan data real dari model
11. **[M-09]** Tambah tenant scope di `QuestionController::index`
12. **[M-05]** Fix `ilike` → `like` (MySQL compatibility)
13. **[M-07]** Implement settings persistence jika diperlukan
14. **[M-06]** Hapus dead code `Result` model atau implementasikan
