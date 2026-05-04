# Mock Exam Plan: Real User Dry Run

Tujuan: Menguji sistem dengan variabel manusia, perangkat, dan jaringan nyata sebelum hari pelaksanaan ujian besar.

## 1. Parameter Uji Coba
- **Peserta**: 100–300 Siswa nyata.
- **Perangkat**: Campuran (Smartphone Android lama, Laptop sekolah, Tablet).
- **Jaringan**: WiFi Sekolah (terbatas) dan Paket Data Seluler.
- **Durasi**: 60 Menit.

## 2. Skenario Pengujian (Chaos Simulation)
1.  **Massive Login**: Semua siswa login dalam rentang waktu 5 menit yang sama.
2.  **Network Drop**: Meminta 10% siswa mematikan koneksi di tengah ujian selama 2 menit, lalu menyalakan kembali.
3.  **Device Switching**: Meminta beberapa siswa pindah dari HP ke Laptop untuk menguji *Multi-Device Guard*.
4.  **Tab Hunting**: Meminta siswa mencoba membuka tab lain untuk memicu *Cheat Log*.

## 3. Checklist Observasi
- [ ] Apakah Reverb menangani reconnect storm dengan lancar?
- [ ] Apakah `fetchWithRetry` berhasil menyelamatkan jawaban saat jaringan putus?
- [ ] Apakah ada perangkat (misal: browser bawaan HP lama) yang gagal me-render soal?
- [ ] Seberapa besar delay sinkronisasi Redis-ke-DB saat beban puncak?

## 4. Evaluasi & Feedback
Setelah selesai, kumpulkan feedback dari siswa dan proktor mengenai:
- Kecepatan loading soal.
- Kejelasan pesan error.
- Kemudahan navigasi.
