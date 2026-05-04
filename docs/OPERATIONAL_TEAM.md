# Operational Team & Roles: Makassar Ujian

Dokumen ini mendefinisikan struktur tim minimal yang diperlukan untuk menjalankan platform ujian di lingkungan produksi secara aman.

## 1. Struktur Tim Minimal (Skeletal Team)

Untuk menghindari *Single Point of Failure* (SPOF), operasional harus dibagi ke dalam tiga peran utama:

### A. Monitoring Operator (L1 Support)
- **Tanggung Jawab**: 
    - Memantau dashboard proktor dan kesehatan sistem secara real-time selama jendela ujian.
    - Menjawab pertanyaan dasar dari pengawas sekolah (proktor).
- **Tool Utama**: 
    - Dashboard "Ujian Berlangsung".
    - Channel Telegram/WhatsApp Support.

### B. Technical Support (L2 Support)
- **Tanggung Jawab**: 
    - Menangani masalah teknis yang tidak bisa diselesaikan L1 (misal: reset session siswa, validasi data answer).
    - Melakukan troubleshooting koneksi atau device spesifik.
- **Tool Utama**: 
    - Database Access (Read-only).
    - Admin Dashboard (User Management).

### C. SRE / System Engineer (L3 Support)
- **Tanggung Jawab**: 
    - Menangani kegagalan infrastruktur (DB down, Redis OOM, Reverb crash).
    - Melakukan patching darurat dan scaling server.
- **Tool Utama**: 
    - Prometheus / Grafana.
    - SSH Access & Cloud Console.

## 2. Jadwal Piket (On-Call)
- Selama periode ujian nasional (H-7 s/d H+1), tim wajib berada dalam status **High Alert**.
- Setiap shift pengerjaan ujian (pagi/siang) harus memiliki minimal 1 Operator dan 1 Technical Support yang standby.

## 3. Jalur Koordinasi
- **Internal**: Slack / Discord channel #ops-room.
- **Eksternal (ke Sekolah)**: Helpdesk Ticket System & Hotline WhatsApp.
