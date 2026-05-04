# Incident Response SOP: Makassar Ujian

SOP ini mengatur tindakan yang harus diambil saat terjadi anomali atau kegagalan sistem di produksi.

## 1. Matriks Eskalasi

| Level | Kategori | Saluran Alert | Penanggung Jawab | Target Respon |
| :--- | :--- | :--- | :--- | :--- |
| **P1** | **CRITICAL**: Ujian Berhenti, DB Down | Phone Call / Telegram | SRE Lead + CTO | < 10 Menit |
| **P2** | **HIGH**: WebSocket Drop, Queue Lambat | Telegram | Senior Developer | < 30 Menit |
| **P3** | **MEDIUM**: Error UI minor, Latency naik | Slack | On-call Dev | < 2 Jam |
| **P4** | **LOW**: Typo, Laporan lambat | Email / Jira | Junior Dev | < 24 Jam |

## 2. Prosedur Respon (SOP)

### Skenario A: Database Connection Spike / Down
1.  **Detect**: Alert "DB Connection High" atau "DB Unreachable".
2.  **Mitigate**: 
    - Cek `pg_stat_activity` untuk query yang lambat.
    - Bunuh (kill) query yang memblokir jika perlu.
    - Restart node aplikasi untuk membersihkan connection pool.
3.  **Recover**: Jika Primary mati, lakukan **failover** ke Replica segera.

### Skenario B: Redis OOM (Out of Memory)
1.  **Detect**: Alert "Redis Memory Usage > 90%".
2.  **Mitigate**: 
    - Jalankan command `MEMORY PURGE`.
    - Periksa apakah ada buffer audit log yang tidak ter-flush.
    - Perbesar (Scale up) RAM instance Redis secara vertikal.

### Skenario C: WebSocket Reconnect Storm
1.  **Detect**: Alert "High Reverb Connection Rate".
2.  **Mitigate**: 
    - Aktifkan mode "Maintenance Light" (tunda login baru).
    - Berikan instruksi ke proktor sekolah melalui grup koordinasi untuk melakukan refresh siswa secara bertahap (batch).

## 3. Post-Mortem Requirement
Setiap insiden **P1** dan **P2** wajib menghasilkan dokumen Post-Mortem dalam 48 jam yang berisi:
- Root Cause Analysis (RCA).
- Timeline kejadian.
- Tindakan pencegahan agar tidak terulang (Action Items).
