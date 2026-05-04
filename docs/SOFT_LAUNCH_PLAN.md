# Soft Launch Strategy: Makassar Ujian

Dokumen ini merinci strategi peluncuran bertahap untuk meminimalkan risiko kegagalan sistem pada hari pertama produksi.

## 1. Fase Peluncuran (Rollout Phases)

### Fase 1: Alpha Pilot (Internal & 1 Sekolah)
- **Target**: 1 Sekolah mitra dengan 50–100 siswa.
- **Fokus**: Validasi stabilitas Reverb dan Octane di lingkungan sekolah yang nyata (WiFi terbatas).
- **Support**: 1 SRE + 1 On-site Support di sekolah.

### Fase 2: Beta Rollout (3–5 Sekolah)
- **Target**: 300–500 siswa bersamaan.
- **Fokus**: Menguji load balancing antar app node dan sinkronisasi Redis-ke-DB saat beban mulai meningkat.
- **Support**: Tim monitoring standby di NOC (Network Operation Center).

### Fase 3: General Availability (Regional)
- **Target**: 10+ Sekolah (1000+ siswa).
- **Fokus**: Skalabilitas penuh dan penanganan tiket bantuan secara massal.

## 2. Kriteria Lolos Fase (Exit Criteria)
Sistem boleh naik ke fase berikutnya jika:
- **Error Rate** < 0.5% dari total request.
- **Rata-rata latency** pengerjaan soal < 200ms.
- **Zero critical bugs** ditemukan oleh proktor atau siswa.

## 3. Rencana Rollback
Jika terjadi kegagalan sistem total pada Fase 2 atau 3:
1.  Segera alihkan koneksi ke node backup (failover).
2.  Jika persistensi data (DB) korup, kembalikan ke snapshot terakhir (max 1 jam ke belakang).
3.  Berikan pengumuman "Jadwal Ujian Ditunda" melalui dashboard admin sekolah.
