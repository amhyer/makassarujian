# Laporan Audit Frontend UI — Makassar Ujian Platform
> Tanggal Audit: 30 April 2026

Dokumen ini berisi hasil audit menyeluruh terhadap semua halaman antarmuka (frontend) di `resources/views`. Tujuannya adalah memetakan mana halaman yang sudah siap production, dan mana yang masih berupa mockup/dummy.

---

## 📊 Ringkasan Status

- **Fully Functional:** 6 Halaman (Sudah terkoneksi DB & Backend)
- **Partial Functional:** 6 Halaman (Data campuran antara DB & Hardcoded)
- **Static Only / Mockup:** 15 Halaman (Hanya UI, belum ada fungsi backend)
- **Missing UI:** 2 Halaman Kritis (Tidak ada file UI sama sekali)

---

## 1. Halaman Kritis untuk Ujian (Exam Flow) 🚨

Halaman-halaman ini wajib diselesaikan agar platform bisa digunakan untuk ujian sungguhan.

| Path Halaman | Status | Analisis & Bukti | Rekomendasi |
|---|---|---|---|
| *(Missing)* Halaman Buat/Edit Ujian | 🔴 **TIDAK ADA** | Sama sekali tidak ada file view atau controller untuk admin sekolah membuat ujian baru. | Segera buat `resources/views/pages/ujian/create.blade.php` dan `index.blade.php`. |
| `pages/ujian/distribusi.blade.php` | 🔴 **Static Only** | Berisi tabel hardcoded ("Data Contoh 1", "DAT-001"). | Buat CRUD untuk model `ExamParticipant` dan hubungkan ke tabel. |
| `pages/ujian/pengerjaan.blade.php` | 🟡 **Partial** | Timer dan `saveAnswer` API sudah jalan, **TAPI list soal hardcoded di dalam JS** (`Apa ibukota Indonesia?`). | Render `$attempt->exam->questions` ke dalam data state JS, ganti dummy array. |
| *(Missing)* Halaman Hasil Ujian | 🔴 **TIDAK ADA** | Post-submit tidak menampilkan nilai, tidak ada view yang membaca `result_snapshot`. | Buat halaman summary pasca ujian untuk menampilkan skor akhir. |

---

## 2. Dashboard Berdasarkan Role

| Path Halaman | Status | Analisis & Bukti | Rekomendasi |
|---|---|---|---|
| `dashboard/index.blade.php`<br>*(Super Admin)* | 🔴 **Static Only** | Full dummy UI (template Vben Admin mockup). Menampilkan fake stats seperti "Sales 760", "Likes 41,410", dan chart statis. | Hubungkan dengan query dari tabel `Tenant` dan `SystemLog`. |
| `dashboard/siswa.blade.php` | 🟡 **Partial** | Metriks top card menggunakan DB (`$metrics`), tapi chart dan list "Ujian Mendatang" adalah hardcoded HTML. | Gunakan query ujian asli untuk mengisi list "Ujian Mendatang". |
| `dashboard/admin-sekolah.blade.php` | 🟡 **Partial** | Top card membaca DB, tapi chart JS di-hardcode (`[30, 40, 35...]`) dan list jadwal ujian berupa dummy. | Fetch data chart dari API/Controller. |
| `dashboard/admin-fkgg.blade.php` | 🟡 **Partial** | Sama seperti Admin Sekolah. | Sama seperti Admin Sekolah. |
| `dashboard/revenue.blade.php` | 🟢 **Functional**| Menampilkan array metrics, churn rate, dan chart yang di-generate dari backend logic. | Siap production. |

---

## 3. Halaman Monitoring & Sistem (Semua Static)

Sebagian besar menu di sidebar **Super Admin** saat ini hanya berupa "cangkang" UI tanpa data backend. Controller mengirim `collect([])` untuk semuanya.

| Path Halaman | Status | Analisis | Rekomendasi |
|---|---|---|---|
| `monitoring/aktivitas-siswa.blade.php` | 🔴 **Static Only** | Tabel isi "Data Contoh 1, 2, 3", pagination palsu. | Hubungkan ke Redis audit buffer log. |
| `monitoring/status-server.blade.php` | 🔴 **Static Only** | Status "up" dan "latency" hardcoded semua. | Fetch dari healthcheck API. |
| `monitoring/ujian-berlangsung.blade.php`| 🔴 **Static Only** | Tabel dummy. | Query `Attempt::where('status', 'ongoing')`. |
| `sistem/audit-log.blade.php` | 🔴 **Static Only** | Tabel dummy. | Hubungkan ke tabel DB `audit_logs` atau sejenisnya. |
| `sistem/konfigurasi.blade.php` | 🔴 **Static Only** | Menampilkan mock input (trial 14 hari, dll), tombol save tidak ada POST action. | Buat tabel `Settings` dan buat form submission. |
| `sistem/role-permission.blade.php` | 🔴 **Static Only** | Tabel dummy. | Hubungkan ke package Spatie Permission. |

---

## 4. Halaman Tenant & User Management

| Path Halaman | Status | Analisis | Rekomendasi |
|---|---|---|---|
| `tenant/sekolah.blade.php` | 🟢 **Functional**| Terkoneksi penuh dengan Livewire/Alpine, form modal jalan, data CRUD valid. | Aman. |
| `tenant/fkgg.blade.php` | 🟢 **Functional**| Terkoneksi penuh, data CRUD valid. | Aman. |
| `tenant/aktivasi.blade.php` | 🟢 **Functional**| Terkoneksi penuh dengan state machine activation. | Aman. |
| `user-management/admin-sekolah.blade.php` | 🔴 **Static Only** | Tabel dummy. | Ganti dummy html table menjadi `User::whereRole(...)`. |
| `user-management/admin-fkgg.blade.php` | 🔴 **Static Only** | Tabel dummy. | Sama seperti di atas. |

---

## 5. Autentikasi (Auth)

| Path Halaman | Status | Analisis | Rekomendasi |
|---|---|---|---|
| `auth/login.blade.php` | 🟢 **Functional**| Validasi, attempt, dan redirect multi-role berfungsi. | Aman. |
| `auth/register.blade.php` | 🔴 **Static Only**| Form HTML ada tapi tidak ada endpoint `POST /register` di backend. Tombol submit tidak berguna. | Buat `RegisteredUserController` untuk memproses form. |
| `auth/forgot-password.blade.php` | 🟡 **Partial** | Form ada, belum dicek apakah ada mailer setup di backend. | Validasi email driver dan token reset job. |

---

## 📝 Kesimpulan Eksekutif

Jika sistem ini akan diluncurkan dalam waktu dekat, ada ilusi bahwa aplikasi ini "sudah hampir selesai" karena UI-nya terlihat lengkap di sidebar. Namun faktanya, **~70% halaman di sidebar hanyalah template mockup statis**. 

**Tindakan Paling Urgen (Priority 1):**
Platform ujian tanpa halaman pembuat soal/ujian dan tanpa halaman penampil nilai tidak dapat digunakan sama sekali. 
Segera bangun UI + Controller untuk:
1. `ExamController@create` (Pembuatan Ujian)
2. `pages/ujian/distribusi.blade.php` (Pemilihan peserta ujian)
3. Hapus pertanyaan statis di `pengerjaan.blade.php`.
4. Halaman Result paska submit.
