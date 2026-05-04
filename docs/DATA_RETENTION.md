# Data Governance & Retention Policy: Makassar Ujian

Dokumen ini mengatur siklus hidup data untuk menjaga performa sistem dan kepatuhan audit.

## 1. Kebijakan Retensi Data

| Tipe Data | Periode Retensi | Tindakan Pasca-Retensi | Alasan |
| :--- | :--- | :--- | :--- |
| **Audit Logs** | 30 Hari | Pindahkan ke Cold Storage (S3 Glacier) | Kepatuhan Audit |
| **Cheat/Cheat Logs** | 90 Hari | Pindahkan ke Cold Storage | Barang Bukti |
| **Attempt Answers** | 180 Hari | Hapus (Delete) | Volume Sangat Besar |
| **Hasil Ujian (Score)** | Permanen | Tetap di Database Utama | Data Historis Siswa |
| **Media (Gambar Soal)** | Selama Ujian Aktif | Hapus 1 thn setelah ujian | Menghemat Storage |

## 2. Strategi Backup

- **Full Backup**: Setiap hari pukul 02:00 AM (WIB).
- **Incremental Backup**: Setiap 6 jam.
- **Retention Backup**: Backup harian disimpan selama 30 hari, mingguan selama 12 minggu.
- **Off-site Backup**: Salinan backup wajib disimpan di provider cloud berbeda (misal: AWS -> GCP).

## 3. Mekanisme Pengarsipan Otomatis

Sistem menjalankan job `ArchiveOldData` setiap hari untuk:
1. Menghitung data yang melewati batas retensi.
2. Melakukan kompresi dan upload ke storage eksternal.
3. Menghapus data dari tabel utama (PostgreSQL) untuk menjaga kecepatan query.
