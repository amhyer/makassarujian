# Load Testing Guide

Panduan untuk melakukan load testing pada platform Makassar Ujian.

## Prasyarat

1. Install [k6](https://k6.io/docs/get-started/installation/)
2. Jalankan perintah artisan untuk generate data uji:
   ```bash
   php artisan test:generate-load-data --users=1000
   ```
   *Perintah ini akan membuat exam, mendaftarkan 1000 user, dan generate `tests/LoadTest/users.csv` yang akan dibaca oleh k6.*

## Daftar Test

### 1. Ultimate Load Test (10k Concurrent)
Menguji simulasi beban hingga 10.000 user concurrent, termasuk WebSocket, API Autosave, dan simulasi Cheat Log (Anti-Cheat).

```bash
k6 run scripts/load_tests/ultimate_load_test.js
```

### 2. Circuit Breaker / Chaos Test
Menguji ketahanan sistem saat Redis mengalami kegagalan (`ChaosEngineeringMiddleware`). Memastikan `SafeModeAnswerService` aktif dan fallback ke Database berjalan mulus.

```bash
k6 run scripts/load_tests/circuit_breaker_test.js
```

### 3. Endurance Test (2-Jam Long Run)
Mensimulasikan ujian sungguhan 120 menit nonstop dengan 5.000 user.

**Tujuan:** Deteksi memory leak (Octane/Reverb), Redis fragmentation, dan WS connection drift yang hanya muncul pada long-running execution.

**Metrik Utama yang Dipantau:**
- `memory_degradation_proxy_rtt` — P95/P99 RTT harus **stabil** sepanjang 2 jam (tidak naik)
- `autosave_success_rate` — Harus > 98% selama 2 jam penuh
- `ws_disconnect_rate` — Harus < 2%

```bash
php artisan test:generate-load-data --users=5000
k6 run scripts/load_tests/endurance_test.js
```

> Jalankan ini sambil memantau memory server dengan `watch -n5 'ps aux --sort=-%mem | head -10'`

### 4. Reconnect Storm Test (2.000 User)
Mensimulasikan skenario terburuk: listrik sekolah padam, 2.000 siswa reconnect serentak dalam hitungan detik.

**Tujuan:** Validasi Reverb tidak menjadi bottleneck, Auth API tidak collapse, event tidak lag.

**Metrik Utama:**
- `ws_reconnect_success_rate` — Harus > 95%
- `ws_reconnect_latency_ms` — P95 < 5 detik
- `auth_success_after_reconnect` — Harus > 98%

```bash
php artisan test:generate-load-data --users=2000
k6 run scripts/load_tests/reconnect_storm_test.js
```

## Cara Membaca Hasil

| Threshold | Arti Gagal |
|-----------|------------|
| `http_req_failed > 1%` | Terlalu banyak error — cek log |
| `p(95) RTT creeping up` | Memory leak — restart Octane/Reverb |
| `ws_reconnect_success_rate < 95%` | Reverb overwhelmed — tambah worker |
| `autosave_success_rate < 98%` | Redis atau DB bermasalah |
