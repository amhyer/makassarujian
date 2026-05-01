# Laporan Audit Backend API
> Fokus: Kelengkapan Endpoint, Penggunaan (Usage), Validasi, dan Otorisasi

Audit ini meninjau kondisi rute API (`routes/api.php` & `routes/web.php`) dan *Controllers* untuk memastikan ketersediaan logika *backend* yang melayani UI frontend, serta memeriksa celah pada validasi masukan dan akses otorisasinya.

---

## 🚫 1. Endpoint yang Belum Ada (Missing Endpoints)

Backend saat ini sangat defisit (kekurangan) *controllers* dasar yang esensial untuk manajemen data:
*   **`ExamController` (Kritis):** Tidak ada API untuk CRUD ujian (membuat paket soal, set durasi, dsb).
*   **`StudentExamController`:** Tidak ada API bagi siswa untuk mengambil "Daftar Ujian yang Bisa Diikuti" (Lobby endpoint).
*   **`ResultController`:** Tidak ada API untuk menarik daftar nilai atau rapor paska ujian selesai.
*   **`ClassController` & `SubjectController`:** Admin tidak bisa membuat atau menghapus kelas dan mata pelajaran karena tidak ada *routes*-nya.
*   **`RegisteredUserController`:** Form pendaftaran sekolah di `/register` tidak bisa di-*submit* karena tidak ada *backend handler*-nya.

---

## 👻 2. Endpoint yang Tidak Dipakai Frontend (Unused)

Ada beberapa endpoint yang sudah ditulis dengan sangat baik di backend, tetapi *frontend* UI-nya mengabaikannya:
*   **API Anti-Cheat (`/api/exam/report-tab-switch` & `/api/exam/cheat-log`)**: Logika *throttle* dan logging kecurangan sudah siap di *ExamSessionController*. Namun, *view* `pengerjaan.blade.php` sama sekali tidak mengirim AJAX ke sini saat siswa berpindah tab.
*   **API Questions (`/api/questions`)**: API ini tidak dipakai oleh halaman pengerjaan. Halaman pengerjaan justru mengabaikan database dan memakai *hardcoded list* (*"Apa ibukota Indonesia?"*).

---

## 📦 3. Controller Kosong / Placeholder

Banyak halaman yang terlihat seolah-olah sudah selesai, tetapi *controller*-nya hanyalah cangkang kosong:
*   **`SuperAdminPageController.php`**: Hampir semua method di dalam controller ini (seperti `distribusi()`, `aktivitasSiswa()`, `statusServer()`, `konfigurasi()`) hanyalah mereturn `return view('pages.x.y', ['data' => collect([])])`. 
*   **Dampak:** Hal ini menciptakan persepsi palsu bahwa aplikasi sudah punya banyak fitur, padahal tidak ada logika *database* sama sekali di baliknya.

---

## 🛡️ 4. Evaluasi Validasi (Validation)

*   **Status: BAIK (🟢)**
*   Secara umum, validasi *Request* sudah terimplementasi dengan baik. `ExamSessionController` (`start()`, `saveAnswer()`) menggunakan `$request->validate()` untuk memastikan `exam_id` dan `question_id` wajib disi.
*   Penggunaan *FormRequest* khusus (seperti `StoreQuestionRequest`) membantu meringankan beban pengontrol utama.

---

## 🔓 5. Celah Otorisasi (Authorization Flaws)

Ini adalah masalah keamanan (Security) yang sangat kritis di level bisnis *logic*:

*   **Celah pada `/api/exam/start` (Kritis):**
    *   Sistem memvalidasi keberadaan *Exam* (`$request->validate(['exam_id' => 'exists:exams,id'])`).
    *   **TAPI**, sistem **TIDAK** memverifikasi apakah *User* yang sedang *login* berhak mengikuti ujian tersebut (misal, tidak dicek dengan tabel `exam_participants`).
    *   *Skenario Serangan:* Siswa kelas 10 yang mengetahui UUID ujian kelas 12 bisa memanggil API ini dan *start attempt* ujian kelas 12 tanpa izin.
*   **Gate Policy di `QuestionController`:**
    *   Fungsi-fungsi menggunakan `Gate::authorize('viewAny', Question::class);`. Jika konfigurasi Spatie Permissions belum tersinkronisasi sempurna, ini akan memblokir (*403 Forbidden*) pengguna sah.

---

## 🛠️ Kesimpulan & Tindakan Lanjutan

API Backend memiliki tulang punggung (*scaling*, *redis caching*) yang sangat superior, tetapi tulang rusuknya (CRUD dasar) belum dibangun.

**Yang harus dilakukan segera:**
1.  Buat **`ExamController`** agar alur ujian bisa dimulai dari admin.
2.  Tambahkan validasi otorisasi di **`ExamSessionController@start`** (periksa tabel pendaftar / peserta sebelum mengizinkan sesi dimulai).
3.  Implementasikan JavaScript yang memanggil API Anti-Cheat pada halaman pengerjaan.
