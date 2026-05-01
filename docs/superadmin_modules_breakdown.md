# Roadmap Pengerjaan Modul Super Admin

Berdasarkan audit pada `sidebar-menu.blade.php` dan `SuperAdminPageController.php`, sebagian besar antarmuka (UI) Super Admin saat ini **sudah memiliki tampilan yang cantik**, namun **backend-nya masih menggunakan data dummy** (mengirim `collect([])` dan angka `0`).

Berikut adalah breakdown pengerjaan (_Backlog_) yang harus segera dieksekusi agar Dasbor Super Admin benar-benar fungsional dan berguna untuk proyek SaaS Makassar Ujian.

## 📊 Kategori 1: Monitoring & Observability (Prioritas Tinggi)

Super Admin butuh visibilitas penuh terhadap apa yang terjadi di platform secara _real-time_.

- [x] **Ujian Berlangsung (`ujianBerlangsung`)**
    - **Kebutuhan**: Menarik data dari Redis untuk melihat jumlah ujian yang sedang aktif secara _live_ di semua sekolah.
    - **Tindakan**: Query tabel `attempts` yang berstatus `ongoing` dan kalkulasi metrik `active_sessions`, `total_participants`.
- [x] **Status Server (`statusServer`)**
    - **Kebutuhan**: Dasbor kesehatan infrastruktur.
    - **Tindakan**: Buat _script_ pengecekan koneksi Redis, memori Redis, CPU/RAM server, dan koneksi Database menggunakan `SystemHealthController`.
- [x] **Aktivitas & Log Kecurangan (`aktivitasSiswa`)**
    - **Kebutuhan**: Melihat siapa saja yang mencoba curang.
    - **Tindakan**: Query tabel `cheat_logs` yang digabungkan (_join_) dengan tabel `users` dan `tenants`.

## 👥 Kategori 2: User Management

Super Admin harus bisa mengontrol para administrator di level bawahnya.

- [x] **Admin Sekolah (`adminSekolah`)**
    - **Kebutuhan**: CRUD (Create, Read, Update, Delete) untuk pengguna dengan peran `School Admin`.
    - **Tindakan**: Pindahkan dari data dummy ke query tabel `users` dengan peran `School Admin` beserta filter nama sekolah.
- [x] **Admin FKGG (`adminFkgg`)**
    - **Kebutuhan**: CRUD untuk peran `FKKG Admin`.
    - **Tindakan**: Sama dengan atas, namun untuk pengguna FKGG.

## ⚙️ Kategori 3: Sistem & Keamanan

Pusat kendali pengaturan aplikasi.

- [x] **Konfigurasi Global (`konfigurasi`)**
    - **Kebutuhan**: Mengubah pengaturan (seperti _default trial days_, nama platform) tanpa perlu masuk ke `.env`.
    - **Tindakan**: Buat tabel `settings` atau simpan di file JSON khusus yang bisa ditulis ulang via UI.
- [x] **Audit Log (`auditLog`)**
    - **Kebutuhan**: Memantau siapa melakukan apa (Penting untuk keamanan SaaS).
    - **Tindakan**: Tampilkan isi dari tabel `audit_logs` (yang sebelumnya kita sudah buat modelnya) ke dalam bentuk tabel data.

## 📚 Kategori 4: Ujian Global

Pengaturan _template_ yang bisa dipakai lintas-sekolah.

- [ ] **Template Ujian (`template`)**
    - **Kebutuhan**: Super Admin membuat paket soal "Master" yang bisa di- _copy_ oleh sekolah-sekolah.
    - **Tindakan**: CRUD tabel `exams` namun dikhususkan dengan flag `is_template = true` (tanpa diikat ke satu `tenant_id` khusus).
- [ ] **Distribusi Ujian (`distribusi`)**
    - **Kebutuhan**: Melacak ujian mana yang sudah didistribusikan secara global.

## 📩 Kategori 5: Support & Bantuan

- [ ] **Sistem Tiket (`tiket`)**
    - **Kebutuhan**: Menampung keluhan/laporan _error_ dari Admin Sekolah.
    - **Tindakan**: Buat tabel `tickets` (jika belum ada) dan _controller_ untuk membalas keluhan pelanggan.
- [ ] **Broadcast Notifikasi (`broadcast`)**
    - **Kebutuhan**: Mengirim pengumuman _maintenance_ atau fitur baru ke seluruh pengguna.
    - **Tindakan**: Buat tabel `announcements` yang nanti dirender di dasbor setiap sekolah.

---

## 🎯 Rekomendasi Eksekusi

Untuk tahap pertama, saya sarankan kita **fokus menyelesaikan Kategori 1 (Monitoring) & Kategori 3 (Audit Log)** terlebih dahulu, karena fitur ini paling esensial bagi Anda sebagai pemilik SaaS untuk memantau kelancaran aplikasi dan keamanan sistem setelah rilis awal.
