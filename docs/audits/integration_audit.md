# Laporan Audit Integrasi Antar Modul
> Fokus: Keterhubungan Modul & Risiko *Silo*

Audit ini memeriksa apakah modul-modul utama dalam sistem Makassar Ujian telah saling berkomunikasi dengan baik, atau justru berdiri sendiri (*silo*) tanpa integrasi yang aman.

---

## 1. Integrasi: Billing ↔ Tenant Activation

| Status | 🟢 Terhubung (Backend) / 🔴 Terputus (Frontend) |
| :--- | :--- |

**Analisis:**
*   **Backend (Kuat):** Alur logika di `WebhookController.php` sudah sangat baik. Saat webhook dari *payment gateway* (Midtrans/Xendit) masuk membawa status `settlement`, sistem akan memanggil `$paymentService->markPaid()` yang secara berantai memanggil `$subscriptionService->activate()`. Ini akan memicu event `TenantActivated`.
*   **Frontend (Putus):** Halaman UI untuk *upgrade* paket langganan dan *checkout* belum terhubung ke API pembuatan *Invoice*. Admin tidak punya cara menekan tombol "Bayar" di antarmuka.

---

## 2. Integrasi: Tenant ↔ Exam Data (Kritis!)

| Status | 🔴 Terputus (Fatal Security Risk) |
| :--- | :--- |

**Analisis:**
*   **Kondisi:** Platform ini didesain sebagai *SaaS Multi-Tenant* (banyak sekolah dalam satu aplikasi).
*   **Gap Integrasi:** Modul Inti Ujian (`Exam`, `Attempt`, `ExamSession`, `AttemptAnswer`) **tidak diintegrasikan** dengan sistem *Tenant*. Mereka kehilangan Trait `BelongsToTenant` (atau `TenantScope` setara) di level *Eloquent Model*.
*   **Dampak (Data Leak):** Data ujian berdiri sendiri (*siloed* dari scope tenant). Jika seorang siswa di *Sekolah A* menebak UUID ujian milik *Sekolah B*, query database akan meloloskannya karena tidak ada filter `where('tenant_id', current_tenant)` yang disisipkan secara otomatis oleh framework.

---

## 3. Integrasi: User ↔ Role ↔ Tenant

| Status | 🟡 Sebagian Terhubung (Ada Gap Registrasi) |
| :--- | :--- |

**Analisis:**
*   **Kondisi:** Sistem menggunakan *Spatie Permission* untuk *Role*, dan *Foreign Key* `tenant_id` pada tabel `users`.
*   **Gap 1 (Registrasi Putus):** Modul registrasi sekolah baru terputus total (tidak ada `RegisterController`). Oleh karena itu, rantai otomatisasi: *Buat Tenant ➡️ Buat User ➡️ Assign Role School Admin* tidak bisa berjalan. Saat ini semua harus di-*inject* manual via *Seeder*.
*   **Gap 2 (FKKG Terisolasi):** Terdapat rute antarmuka untuk "FKKG Admin" (Forum Kelompok Kerja Guru), tetapi peran ini tidak dihubungkan ke sistem *Role Spatie*. Modul FKKG berdiri sendiri sebagai pulau yang tidak bisa diakses oleh siapapun secara sah.

---

## 🛑 Kesimpulan (Daftar Integration Gap Utama)

Berikut adalah daftar modul yang masih berdiri sendiri (*Silo*) atau kehilangan "lem" integrasinya:

1.  **Modul Registrasi & Onboarding (Silo):** Belum terhubung dengan pembuatan Tenant dan *Role Assignment*.
2.  **Modul Transaksi Ujian (Bocor):** Belum terhubung dengan *Global Scope Tenant*. Beroperasi sebagai tabel global, bukan tabel spesifik sekolah.
3.  **UI Checkout Langganan (Silo):** Tampilan halaman harga tidak terhubung dengan *Service Billing* di backend.
4.  **UI Ujian Siswa (Silo):** Antarmuka pengerjaan (Vue/JS) terisolasi dari *Question Database*. UI memilih memakai soal *hardcoded* daripada menarik relasi dari tabel `questions`.

**Tujuan Tercapai:** Kita telah memvalidasi bahwa sistem saat ini tidak hanya kekurangan fitur, tetapi fitur yang ada pun belum saling "berbicara" dengan aman, terutama di area isolasi data antar sekolah.
