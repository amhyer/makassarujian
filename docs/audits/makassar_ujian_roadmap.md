# Roadmap Perbaikan & Pengembangan Makassar Ujian
> Disusun berdasarkan 6 Hasil Audit Menyeluruh (UI, Engine, DB, API, Integrasi, Role)

Dokumen ini adalah *Blueprint* langkah demi langkah untuk menyulap aplikasi dari kondisi saat ini (*mockup/shell* dengan *engine backend* yang terputus) menjadi produk *SaaS* yang siap produksi (*Production-Ready*).

---

## 🚨 Phase 1: Critical Fix (Harus Jalan Dulu)
*Fase ini fokus pada "penyelamatan nyawa" aplikasi. Memastikan ujian bisa dibuat, dikerjakan, dan disubmit tanpa crash atau kebocoran data.*

1.  **Tambal Kebocoran Data (Tenant Isolation)**
    *   Terapkan *trait* `BelongsToTenant` / *Global Scope* pada model: `Exam`, `Attempt`, `ExamSession`, `AttemptAnswer`.
    *   **Tujuan:** Mencegah kebocoran data lintas sekolah (IDOR).
2.  **Sembuhkan Bug Skoring (Fatal Error)**
    *   Perbaiki `ScoreCalculator` yang saat ini *crash* karena mencari kolom `correct_option` yang tidak ada di database. Arahkan untuk membaca data dari kolom JSON.
3.  **Bangun Fondasi Pembuatan Ujian**
    *   Buat `ExamController` (API & Web) agar Admin bisa merangkai soal menjadi paket ujian.
4.  **Hidupkan Antarmuka Ujian Siswa**
    *   Bongkar soal statis (*"Apa ibukota Indonesia?"*) di `pengerjaan.blade.php`.
    *   Hubungkan *Vue/Alpine.js* agar menarik dan me-render soal asli dari database.
5.  **Tutup Celah Otorisasi Mulai Ujian**
    *   Perketat `ExamSessionController@start`. Cek apakah siswa benar-benar terdaftar di kelas yang berhak mengikuti ujian tersebut sebelum mengizinkan sesi dimulai.

---

## 🏗️ Phase 2: Core Feature Completion
*Fase melengkapi "kepingan puzzle" fungsionalitas dasar yang saat ini kosong agar siklus pengguna (Admin & Siswa) utuh 100%.*

1.  **Lengkapi Alur Registrasi & Setup**
    *   Buat `RegisterController` agar sekolah baru bisa mendaftar mandiri (terintegrasi dengan pembuatan *Tenant* dan *Role Assignment* otomatis).
    *   Buat `ClassController` dan `SubjectController` untuk Admin sekolah.
2.  **Sistem Pelaporan Nilai (Reporting)**
    *   Buat `ResultController`.
    *   Bangun halaman **Analytics Nilai** untuk guru/admin agar bisa melihat skor siswa yang telah selesai ujian.
3.  **Terapkan Randomisasi Soal**
    *   Implementasikan logika pengacakan di backend berdasarkan *flag* `shuffle_questions` sebelum data dikirim ke UI siswa.
4.  **Integrasi Import Soal Excel**
    *   Sambungkan tombol di halaman UI Bank Soal ke *endpoint* API Import Excel yang sudah ada.

---

## 🎨 Phase 3: Enhancement & UX
*Fase mempercantik dan menghidupkan fitur-fitur "Wow" yang saat ini masih berupa antarmuka statis / pajangan.*

1.  **Rombak Mockup Super Admin**
    *   Hapus logika `return collect([])` di `SuperAdminPageController`.
    *   Sambungkan grafik dasbor, distribusi, dan aktivitas ke data metrik *real-time* dari database.
2.  **Hidupkan Fitur Pengawasan (Proctoring & Anti-Cheat)**
    *   **Anti-Cheat:** Tanamkan JavaScript di halaman ujian untuk mendeteksi siswa yang berpindah *tab* atau meminimalkan *browser*, lalu kirim *event*-nya ke API `cheat-log` yang sudah ada.
    *   **Live Proctor:** Rombak halaman UI "Ujian Berlangsung" agar menarik data dari *endpoint* statistik *live* menggunakan *polling*.
3.  **Bangun "Lobby" Siswa**
    *   Buat halaman khusus bagi siswa untuk memasukkan Token/melihat daftar ujian yang tersedia untuk mereka ikuti.
4.  **Perbaikan Sistem Otorisasi**
    *   Tambahkan `Gate::before` untuk Super Admin di `AppServiceProvider`.
    *   Daftarkan *Role* FKKG Admin ke *Seeder* agar halamannya bisa diakses.

---

## 🚀 Phase 4: Scaling & Optimization
*Fase pematangan akhir untuk memastikan platform siap menghadapi ribuan sekolah secara bersamaan dan stabil secara operasional.*

1.  **Integrasi Frontend Billing & Checkout**
    *   Sambungkan halaman harga (*Pricing*) ke sistem *Invoice* agar siklus Webhook pembayaran berjalan penuh dari ujung ke ujung.
2.  **Tabel Audit Log Nyata**
    *   Ubah tabel statis di UI `audit-log.blade.php` menjadi *Datatable* interaktif yang membaca jejak tindakan pengguna dari database.
3.  **Sistem Auto-Backup Tenant**
    *   Implementasikan *cron job* terjadwal (menggunakan *spatie/laravel-backup*) untuk mencadangkan database secara otomatis ke S3/Cloud.
4.  **Export Nilai (PDF/Excel)**
    *   Tambahkan tombol di halaman Analytics Nilai agar rapor siswa bisa diunduh dalam bentuk dokumen resmi.

---

**Kesimpulan:** 
Roadmap ini memastikan kita tidak membuang waktu mengerjakan hal kosmetik (Phase 3/4) sebelum mesin intinya (Phase 1/2) benar-benar berjalan dan aman dari kebocoran data.
