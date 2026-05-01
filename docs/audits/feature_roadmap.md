# Roadmap Pengembangan Fitur
> Identifikasi Fitur Penting & Analisis Kesenjangan (Gap Analysis)

Dokumen ini memetakan fitur-fitur esensial yang diperlukan untuk menjadikan Makassar Ujian sebagai platform SaaS yang matang (Maturity). Fitur dievaluasi berdasarkan ketersediaan saat ini, dan diklasifikasikan berdasarkan prioritas pengembangannya.

---

## 1. Core (Mesin Utama)

| Fitur | Status Saat Ini | Prioritas | Tindakan Lanjutan |
| :--- | :--- | :---: | :--- |
| **Import Soal Excel** | 🟡 Partial | High | API Backend (`QuestionController@importExcel`) menggunakan *job queue* sudah ada. **Tindakan:** Pastikan *frontend* (Vue) di `bank-soal.blade.php` memiliki tombol dan *progress bar* untuk mengunggah file. |
| **Randomisasi Soal** | 🟡 Partial | High | Database dan Model `Exam` sudah mendukung `shuffle_questions` & `shuffle_options`. **Tindakan:** Modifikasi *Controller* saat mengambil soal (`ExamSessionController`) agar memanggil fungsi pengacakan ini sebelum di-lempar ke UI siswa. |
| **Auto Grading** | 🔴 Broken | Critical | Logika skor sudah ada di `ScoreCalculator`. **Tindakan:** Perbaiki *Fatal SQL Bug* (kolom `correct_option` yang tidak ada) agar auto-grading bisa berjalan mulus. |

---

## 2. Monitoring (Pengawasan Ujian)

| Fitur | Status Saat Ini | Prioritas | Tindakan Lanjutan |
| :--- | :--- | :---: | :--- |
| **Cheat Detection (Deteksi Curang)** | 🟡 Partial | High | Endpoint `/api/exam/cheat-log` dengan *throttling* sudah siap. **Tindakan:** Tambahkan *event listener* JavaScript (`visibilitychange`, `blur`) di halaman `pengerjaan.blade.php` untuk mendeteksi saat siswa keluar *fullscreen* atau pindah *tab*, lalu tembak API tersebut. |
| **Live Proctor Dashboard** | 🔴 Mockup | Medium | Terdapat endpoint `/proctor/exam/{examId}/stats` tapi tidak terpakai. **Tindakan:** Rombak halaman statis `ujian-berlangsung.blade.php` agar membaca data dari API ini menggunakan *polling* atau *WebSockets* untuk melihat siswa yang sedang ujian secara *live*. |

---

## 3. Reporting (Pelaporan & Analitik)

| Fitur | Status Saat Ini | Prioritas | Tindakan Lanjutan |
| :--- | :--- | :---: | :--- |
| **Analytics Nilai** | 🔴 Belum Ada | Critical | Skor ujian tersimpan diam di tabel `attempts`. **Tindakan:** Buat `ResultController` dan antarmuka untuk guru/admin guna melihat daftar nilai, nilai rata-rata, nilai tertinggi/terendah per kelas. |
| **Export PDF/Excel** | 🔴 Belum Ada | Medium | **Tindakan:** Pasang *library* seperti `maatwebsite/excel` atau `barryvdh/laravel-dompdf`. Buat tombol di halaman Analytics Nilai agar admin bisa mengunduh rekapitulasi rapor/ujian. |

---

## 4. System (Infrastruktur & Keamanan)

| Fitur | Status Saat Ini | Prioritas | Tindakan Lanjutan |
| :--- | :--- | :---: | :--- |
| **Audit Log UI** | 🔴 Mockup | Low | *Backend* `AuditLog` sudah menyimpan seluruh jejak klik dan perubahan (dengan sangat detail). **Tindakan:** Rombak tampilan `audit-log.blade.php` yang tadinya berisi *dummy html* menjadi *Datatable* asli yang menarik data dari database. |
| **Auto Backup Data** | 🔴 Belum Ada | Low | **Tindakan:** Integrasikan *package* `spatie/laravel-backup`. Konfigurasikan jadwal harian (CRON) untuk men-zip database dan mengunggahnya ke S3/Google Drive guna mencegah bencana kehilangan data tenant. |

---

## 🚀 Kesimpulan Prioritas Eksekusi (Roadmap)

Jika Anda ingin platform ini bisa *Go-Live* (diluncurkan) dengan aman, berikut adalah urutan *Sprint* yang direkomendasikan:

1.  **Sembuhkan Mesin Inti (Critical Phase):** Perbaiki Auto-Grading (Bug SQL) dan pastikan Randomisasi Soal terhubung dengan benar ke UI Siswa.
2.  **Lengkapi Tampilan (Usability Phase):** Buat Analytics Nilai (Halaman Rapor) agar guru punya alasan menggunakan platform ini. Lengkapi tombol Import Excel.
3.  **Perketat Pengawasan (Security Phase):** Hidupkan fitur Cheat Detection di browser siswa dan hidupkan Live Proctor Dashboard.
4.  **Fitur Tambahan (Polishing Phase):** Selesaikan Export PDF, UI Audit Log, dan Sistem Auto-Backup.
