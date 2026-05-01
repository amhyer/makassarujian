# Laporan Audit Role & Permission System
> Fokus: Matriks Akses, Keamanan, dan Celah Otorisasi

Sistem ini menggunakan library `spatie/laravel-permission`. Audit ini memeriksa bagaimana peran (Role) dan hak akses (Permission) didistribusikan, serta menganalisis potensi celah keamanan (*over-permission* atau kegagalan isolasi).

---

## 🗝️ Matriks Role vs Permission

Berikut adalah tabel distribusi hak akses yang tercatat di dalam sistem (berdasarkan `RoleAndPermissionSeeder.php`):

| Role | manage_tenants | manage_users | manage_exams | take_exams | view_reports |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Super Admin** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **School Admin**| ❌ | ✅ | ✅ | ❌ | ✅ |
| **Teacher** (Guru)| ❌ | ❌ | ✅ | ❌ | ✅ |
| **Student** (Siswa)| ❌ | ❌ | ❌ | ✅ | ❌ |
| **FKKG Admin** | ❓ | ❓ | ❓ | ❓ | ❓ |

---

## 🚨 Analisis & Temuan Celah Keamanan

### 1. Peran `FKKG Admin` Tidak Terdaftar (Missing Role)
*   **Kondisi:** Di dalam `routes/web.php` terdapat rute yang dilindungi oleh middleware `role:FKKG Admin` (contoh: `/fkkg/dashboard`).
*   **Masalah:** Di dalam file `RoleAndPermissionSeeder.php`, peran `FKKG Admin` **sama sekali tidak dibuat** dan tidak diberi *permission* apapun.
*   **Dampak:** Pengguna yang di-assign sebagai Admin FKKG akan mengalami *Error 403 Forbidden* secara permanen karena role-nya tidak terdaftar secara resmi di database *Spatie*.

### 2. Ketiadaan Bypass `Gate::before` untuk Super Admin
*   **Kondisi:** Normalnya pada implementasi *Spatie*, ada fungsi `Gate::before()` di *AppServiceProvider* yang menjamin Super Admin selalu mendapatkan nilai `true` untuk semua akses, apapun *permission* fisiknya di DB.
*   **Masalah:** Fungsi ini hilang.
*   **Dampak:** Super Admin saat ini murni bergantung pada sinkronisasi row di tabel `role_has_permissions`. Jika developer menambahkan *permission* baru (misal: `manage_billing`) tapi lupa menjalankan seeder, Super Admin tidak akan bisa mengakses fitur tersebut.

### 3. Risiko *Over-Permission* pada Guru (Teacher)
*   **Kondisi:** Guru diberikan *permission* global `manage_exams`.
*   **Masalah:** *Spatie permission* bersifat "Ya/Tidak" (Boolean). Memiliki akses `manage_exams` artinya bisa menembak endpoint CRUD ujian.
*   **Dampak:** Kecuali ada *Policy* (kelas pembatas) tambahan di level Controller yang secara eksplisit mengecek `if ($exam->created_by === Auth::id())`, maka Guru A bisa mengubah, menghapus, atau melihat kunci jawaban ujian yang dibuat oleh Guru B di sekolah yang sama. Ini adalah pelanggaran batas wewenang (*over-permission*).

### 4. Ilusi Keamanan Tanpa *Tenant Scoping*
*   **Kondisi:** Otorisasi peran hanya membukakan pintu.
*   **Masalah:** Meskipun *School Admin* dibatasi oleh `manage_users`, jika endpoint API tidak mengecek `tenant_id`, ia tetap bisa memanipulasi *user* dari sekolah lain.
*   **Dampak:** (Terkait dengan temuan audit database sebelumnya), ini membuka celah Insecure Direct Object Reference (IDOR) lintas sekolah jika Global Scope absen.

---

## 🛠️ Rekomendasi Solusi

1.  **Daftarkan FKKG Admin:** Update `RoleAndPermissionSeeder.php` untuk memuat `$fkkgAdmin = Role::firstOrCreate(['name' => 'FKKG Admin']);` dengan *permissions* yang identik atau mirip dengan *School Admin*.
2.  **Pasang `Gate::before`:** Tambahkan kode berikut di `AppServiceProvider` pada method `boot()`:
    ```php
    Gate::before(function ($user, $ability) {
        return $user->hasRole('Super Admin') ? true : null;
    });
    ```
3.  **Terapkan Laravel Policies:** Untuk mencegah Guru mengedit ujian orang lain, pastikan `ExamController` nantinya dilindungi oleh `ExamPolicy` yang mengecek kepemilikan objek (`$user->id === $exam->created_by`).
