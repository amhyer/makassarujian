# Makassar Ujian - Incident Response Playbook

Playbook ini dirancang sebagai panduan langkah-demi-langkah (SOP) untuk merespons insiden secara cepat pada infrastruktur SaaS ujian. Ikuti instruksi di bawah berdasarkan gejala yang dilaporkan.

---

## 🛑 Kategori 1: Data Ujian Hilang atau Tidak Tersimpan
**Gejala:** Nilai tidak muncul di dashboard, "Dashboard Drift", atau jawaban peserta *rollback*.
**Akar Masalah Umum:** Redis Eviction, kegagalan sinkronisasi *autosave* dari Redis ke DB.

### Langkah Debugging:
1. **Cek Kesehatan Sinkronisasi Redis-DB**
   Jalankan command validator untuk melihat sisa *dirty attempts* yang belum disapu ke DB:
   ```bash
   php artisan exam:sync:health
   ```
   *Metric yang dicek:* `Wait Time` (harus < 60 detik) dan `Jobs Pending` (harus < 5000).
2. **Validasi Keseimbangan Counter (Dashboard Drift)**
   Perbaiki *drift* perhitungan di dashboard dengan *command healing* (ganti {examId}):
   ```bash
   php artisan exam:counter:validate 1
   ```
3. **Cek Status Global Safe Mode**
   Jika data tidak masuk ke Redis, pastikan apakah server sedang menjalankan *Safe Mode* (menyimpan langsung ke DB). Cek log:
   ```bash
   grep "Global Safe Mode" storage/logs/laravel.log
   ```

---

## 🔌 Kategori 2: Realtime Mati (Proctor Dashboard Beku)
**Gejala:** Progress bar tidak berjalan, *tab switch* tidak terdeteksi, atau log kecurangan (*cheat*) tidak muncul di layar guru.
**Akar Masalah Umum:** Koneksi Laravel Reverb terputus, atau *event flood*.

### Langkah Debugging:
1. **Periksa Endpoint Debug WebSockets**
   Akses endpoint diagnostik *realtime* untuk melihat metrik *Event Per Second* (EPS):
   ```bash
   curl -X GET http://127.0.0.1:8000/api/debug/realtime/{examId}
   ```
   *Metric yang dicek:* 
   - `status`: Jika `FLOOD` (EPS > 50), berarti ada aplikasi me-looping *request*. Jika `MISSING_EVENTS`, berarti *socket* terputus dari klien.
2. **Restart Reverb Server**
   Jika server WebSocket (*Reverb*) membeku, hentikan dan nyalakan ulang:
   ```bash
   php artisan reverb:restart
   ```
3. **Verifikasi Koneksi Klien**
   Buka *browser console* pada klien peserta, dan cek apakah status langganan (*subscribe*) ke saluran `exam.{examId}` sukses (menggunakan Laravel Echo).

---

## 🐌 Kategori 3: Sistem Berjalan Lambat (Lag)
**Gejala:** Menekan "Simpan/Selanjutnya" memakan waktu lebih dari 1 detik, atau halaman utama ujian lambat diakses.
**Akar Masalah Umum:** Penumpukan antrean (*queue backlog*), atau *query* database berat (*N+1 issue*).

### Langkah Debugging:
1. **Periksa Kesehatan Antrean (Horizon Queue)**
   Pantau tumpukan di latar belakang secara *real-time*:
   ```bash
   php artisan queue:health
   ```
   *Metric yang dicek:* 
   - `queue_wait_time` (Harus di bawah 5 detik).
   - `jobs_pending` (Jika menembus > 10.000, command ini akan **otomatis meluncurkan burst worker** untuk membersihkan penumpukan).
2. **Gaskan Worker Tambahan (Manual Scale)**
   Jika *burst worker* otomatis tidak cukup kuat, nyalakan paksa tambahan proses *queue* darurat:
   ```bash
   php artisan queue:work redis --queue=default --stop-when-empty &
   ```
3. **Cek Kinerja Database (PostgreSQL)**
   Lihat *dashboard Observability* Anda (misal Grafana/Prometheus) dan perhatikan *metric*:
   - `Latency P95` (Harus < 200ms).

---

## 🔥 Kategori 4: Error Bertebaran (High Error Rate)
**Gejala:** Pengguna melaporkan pesan "500 Internal Server Error" atau "Koneksi Terputus".
**Akar Masalah Umum:** Konfigurasi *environment* rusak, koneksi Redis/DB terputus parah.

### Langkah Debugging:
1. **Pancing End-to-End Trace System**
   Tembak *endpoint health check* komprehensif untuk mendeteksi komponen mana yang putus (Redis, DB, atau Queue):
   ```bash
   curl -X GET http://127.0.0.1:8000/api/health/system
   ```
   *Output yang dicek:* Anda akan mendapatkan JSON dengan field `trace_id` (contoh: `0123456789abc...`).
2. **Lacak Titik Kegagalan di Sistem Tracing**
   Salin `trace_id` tersebut dan tempelkan ke kolom pencarian di Jaeger / Zipkin UI Anda. Anda akan melihat secara visual di *span* mana *request* tersebut mati.
3. **Baca Tail Logs**
   Saring pesan *error* krusial hari ini di peladen:
   ```bash
   tail -n 200 storage/logs/laravel.log | grep -i "error\|critical"
   ```

---
*Dokumen ini adalah pedoman hidup (living document) dan harus terus diperbarui seiring berkembangnya infrastruktur sistem Ujian.*
