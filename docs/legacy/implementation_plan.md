# Pembuatan Kerangka Halaman Berdasarkan Menu Sidebar

Permintaan Anda untuk membuat "semua isi konten dari menu sidebar" adalah pekerjaan berskala besar karena mencakup **18 sub-menu** berbeda (Mulai dari Manajemen Tenant hingga Support & Notifikasi).

Untuk memastikan semuanya terlihat **profesional ala SaaS premium**, saya mengusulkan pendekatan kerangka dasar (mockup/empty state) berkualitas tinggi.

## User Review Required

> [!IMPORTANT]
> **Pendekatan Desain Konten**
> Karena fungsi *backend* (database) untuk 18 menu ini belum ada, saya akan membuatkan desain antarmuka (*User Interface*) berupa **Tabel Data Tiruan (Mockup Table)** dan **Empty State (Status Kosong)** yang profesional untuk setiap halamannya. 
> 
> Dengan cara ini, jika Anda mengklik menu apa pun di sidebar, Anda tidak akan melihat halaman kosong/rusak, melainkan halaman aplikasi sungguhan yang sudah memiliki *header*, tata letak *card*, dan tombol-tombol aksi siap pakai. Apakah Anda setuju dengan pendekatan ini?

## Proposed Changes

---

### 1. Pendaftaran Rute (Routing)
#### [MODIFY] `routes/web.php`
Mendaftarkan 18 rute baru menggunakan pengelompokan (*grouping*) agar rapi. Contoh:
- `/tenant/sekolah`, `/tenant/fkgg`, `/tenant/aktivasi`
- `/billing/paket`, `/billing/tagihan`, `/billing/pembayaran`
- Dst.

---

### 2. Pembaruan Navigasi Sidebar
#### [MODIFY] `resources/views/layouts/sidebar-menu.blade.php`
Menautkan atribut `href="#"` pada seluruh 18 sub-menu dengan rute-rute aktual yang baru dibuat, serta memberikan indikator penyorotan (*active state*) agar pengguna tahu mereka sedang berada di menu mana.

---

### 3. Pembuatan Tampilan (Views)
Saya akan mengelompokkan pembuatan direktori dan file menjadi beberapa kategori. Setiap halaman akan memiliki *layout* seragam: Judul, *Breadcrumbs*, Kotak Pencarian, Tombol "Tambah Baru", dan Tabel/Area Konten.

#### Kategori Manajemen Tenant
- `resources/views/tenant/sekolah/index.blade.php`
- `resources/views/tenant/fkgg/index.blade.php`
- `resources/views/tenant/aktivasi/index.blade.php`

#### Kategori Billing & Subscription
- `resources/views/billing/paket/index.blade.php`
- `resources/views/billing/tagihan/index.blade.php`
- `resources/views/billing/pembayaran/index.blade.php`
- `resources/views/billing/trial/index.blade.php`

#### Kategori Ujian Global
- `resources/views/ujian/bank-soal/index.blade.php`
- `resources/views/ujian/distribusi/index.blade.php`
- `resources/views/ujian/template/index.blade.php`

#### Kategori Monitoring, User Management, Sistem, dan Support
- *(Semua file blade akan dibuat di dalam foldernya masing-masing dengan antarmuka tabel pencarian atau metrik dashboard yang profesional).*

---

## Verification Plan
1. Menyimpan dan merefresh halaman.
2. Mengklik setiap menu di *sidebar* satu per satu.
3. Memastikan semua tautan berfungsi dan menampilkan halaman berdesain profesional dengan transisi yang mulus, tanpa ada *error 404*.
