# Master Execution Plan & WBS (Work Breakdown Structure)
> Proyek: SaaS Makassar Ujian (Platform CBT)
> Dokumen ini adalah panduan utama untuk mengeksekusi perbaikan dan pengembangan platform.

## 🔄 Alur Kerja (Workflow) Pengerjaan Tim/Developer

Agar pengerjaan rapi dan tidak tumpang tindih, ikuti alur berikut untuk setiap tugas:
1. **Pilih Task:** Ambil satu tugas dari **Sprint** yang sedang aktif. Dilarang melompat ke Sprint berikutnya sebelum Sprint saat ini rampung 100%.
2. **Kerjakan & Terapkan Perubahan:** Modifikasi *Controller*, *Model*, atau *View* sesuai target file yang tertulis.
3. **Verifikasi & Testing:** Lakukan pengujian *End-to-End* (E2E) lokal. Pastikan tidak merusak fitur lain.
4. **Checklist:** Tandai tugas menjadi selesai dengan mengubah `[ ]` menjadi `[x]`.

---

## 📋 WBS - Pembagian Sprint

### 🚨 Sprint 1: Penambalan Kritis & Keamanan Data (Blocker)
*Target: Mencegah error HTTP 500 dan kebocoran data antar sekolah.*

- [ ] **Task 1.1: Implementasi Tenant Isolation (Anti Bocor Data)**
  - **Target File:** `App\Models\Attempt`, `ExamSession`, `AttemptAnswer`, `AuditLog`
  - **Aksi:** Tambahkan `use App\Modules\Tenant\Traits\BelongsToTenant;` agar data sekolah otomatis terisolasi.
- [ ] **Task 1.2: Registrasi Route API Ujian**
  - **Target File:** `routes/api.php`
  - **Aksi:** Tambahkan `->name('api.exam.save-answer')` dan `->name('api.exam.session')` agar *frontend* Vue tidak mengalami *Route Not Found*.
- [ ] **Task 1.3: Perbaikan Bug Fatal Skoring**
  - **Target File:** `App\Services\ScoreCalculator.php`
  - **Aksi:** Ubah algoritma pencarian kunci jawaban agar membaca dari *snapshot JSON* milik soal, bukan dari tabel `options` yang sudah dihapus.
- [ ] **Task 1.4: Cleanup Dead Code Database**
  - **Target File:** `App\Models\Option`, `Result`, `Answer`, `AttemptQuestion`
  - **Aksi:** Hapus file model yang sudah usang agar arsitektur bersih.

### 🏗️ Sprint 2: Core Exam Flow (Mesin Utama)
*Target: Admin bisa merakit soal menjadi ujian, dan siswa bisa mengerjakan soal asli hingga selesai.*

- [ ] **Task 2.1: Pembuatan ExamController**
  - **Target File:** `App\Http\Controllers\ExamController.php`, `routes/web.php`
  - **Aksi:** Buat CRUD standar agar Admin bisa membuat paket ujian baru dan mengaitkannya dengan Bank Soal.
- [ ] **Task 2.2: Otorisasi Mulai Ujian**
  - **Target File:** `App\Http\Controllers\ExamSessionController.php` (`start` method)
  - **Aksi:** Validasi ketat bahwa *User* yang *login* benar-benar berhak/terdaftar di ujian tersebut sebelum sesi dibuat.
- [ ] **Task 2.3: Hubungkan UI Pengerjaan dengan Data Database**
  - **Target File:** `resources/views/pages/ujian/pengerjaan.blade.php`, `StudentExamController.php`
  - **Aksi:** Buang *hardcoded array* (soal "Ibukota Indonesia") dari file Vue/Alpine, injeksikan `$attempt->exam->questions` dalam format JSON.
- [ ] **Task 2.4: Halaman Post-Submit (Hasil Ujian)**
  - **Target File:** `ResultController.php`, `resources/views/pages/ujian/hasil.blade.php`
  - **Aksi:** Buat rute dan tampilan agar setelah ujian di-*submit*, siswa dialihkan ke halaman skor atau "Ujian Selesai" dengan sukses.

### 👥 Sprint 3: Distribusi & Dasbor Pengguna
*Target: Menghubungkan proses *Backend* ke dalam UI yang bisa diklik oleh *User*.*

- [ ] **Task 3.1: Fitur Distribusi Ujian (Admin)**
  - **Target File:** `DistributionController.php`, Tampilan Distribusi Ujian.
  - **Aksi:** Buat antarmuka dan *endpoint* untuk menetapkan (*assign*) ujian ke siswa/kelas tertentu (memasukkan data ke tabel `exam_participants`).
- [ ] **Task 3.2: Perbaikan Dashboard Siswa**
  - **Target File:** `resources/views/dashboard/siswa.blade.php`, `DashboardService.php`
  - **Aksi:** Hapus HTML statis. Looping "Ujian Tersedia" dari database agar siswa bisa menekan tombol "Mulai" pada ujian yang valid.
- [ ] **Task 3.3: Self-Registration Siswa/Sekolah**
  - **Target File:** `RegisterController.php`, `routes/web.php`
  - **Aksi:** Buat *handler* POST untuk `/register` agar pendaftaran mandiri berfungsi (termasuk *assign role* dan pembuatan *tenant*).

### 📊 Sprint 4: Observability & Super Admin Modules
*Target: Menghidupkan metrik dan tabel pemantauan untuk Admin Pusat.*

- [ ] **Task 4.1: Metrik Dasbor Real-time**
  - **Target File:** `SuperAdminPageController.php`, View Monitoring.
  - **Aksi:** Ganti `collect([])` statis dengan kueri ke `Attempt::where('status', 'ongoing')` dan `SystemHealthController`.
- [ ] **Task 4.2: Audit Log & Cheat Detection UI**
  - **Target File:** `audit-log.blade.php`, `aktivitas-siswa.blade.php`
  - **Aksi:** Hubungkan tabel UI dengan model `AuditLog` dan tangkapan dari *API Anti-Cheat* yang sudah ada.

### 🚀 Sprint 5: Optimasi Skalabilitas Masa Depan
*Target: Membawa aplikasi ke level Enterprise SaaS.*

- [ ] **Task 5.1: Migrasi Live Proctoring ke WebSockets**
  - **Target File:** `routes/channels.php`, Setup *Laravel Echo*.
  - **Aksi:** Ganti mekanisme *polling* HTTP di halaman Pengawasan Ujian menggunakan teknologi *Push/WebSockets* agar server tidak kelebihan beban.
- [ ] **Task 5.2: Pengembangan Bank Soal Cerdas**
  - **Aksi:** Rancang fitur *tagging* kompetensi dan pelacakan performa per soal (analitik tingkat kesulitan) untuk menambah nilai jual platform.
- [ ] **Task 5.3: Sistem Paket & Langganan (Billing)**
  - **Aksi:** Implementasikan logika *Subscription* yang bisa membatasi jumlah siswa atau fitur sesuai paket langganan yang dipilih sekolah.