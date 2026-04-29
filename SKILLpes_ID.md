---
name: pest-testing
description: "Pengujian platform SaaS ujian menggunakan Pest 3. Mencakup multi-tenant, lifecycle ujian, attempt, autosave, realtime, dan anti-cheat."
---

# Pest Testing – Konteks Platform Ujian

## Ruang Lingkup

Aplikasi ini adalah SaaS ujian, bukan CRUD biasa. Pengujian WAJIB mencakup:

- Isolasi multi-tenant (tenant_id)
- Lifecycle ujian (buat → mulai → attempt → submit)
- Keandalan autosave
- Validasi realtime
- Anti-cheat

---

## Domain Pengujian

### 1. Multi-Tenant
- Data antar tenant tidak boleh bocor
- Semua query harus scoped tenant

### 2. Lifecycle Ujian
- Create exam
- Assign peserta
- Start exam
- Attempt
- Submit

### 3. Attempt (KRITIKAL)
- Satu user = satu attempt aktif
- Resume jika koneksi putus
- Timer dari server

### 4. Autosave
- Jawaban tersimpan berkala
- Tidak hilang saat refresh

### 5. Anti-Cheat
- Deteksi multi login
- Token validasi
- Redis session lock

### 6. Realtime
- Event:
  - student:joined
  - student:left
  - exam:started

---

## Strategi Test

| Layer | Jenis |
|------|------|
| API | Feature |
| Service | Unit |
| Realtime | Mock |

---

## Aturan

- Jangan hanya test happy path
- Wajib test:
  - edge case
  - failure case
  - concurrency
