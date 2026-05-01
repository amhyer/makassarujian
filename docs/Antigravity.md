=== aturan platform ujian ===

# Aturan Sistem Ujian SaaS

## Arsitektur

- Sistem multi-tenant
- Semua query harus isolasi tenant
- Tidak boleh ada kebocoran data

---

## Engine Ujian

- Exam ≠ Attempt
- Attempt adalah snapshot immutable
- Soal harus random per attempt

---

## Timer

- Timer hanya dari server
- Client tidak dipercaya

---

## Autosave

- Jawaban disimpan berkala
- Gunakan queue bila perlu

---

## Realtime

- WebSocket untuk:
  - monitoring
  - status siswa

---

## Anti-Cheat

- 1 user = 1 session
- Gunakan Redis lock
- Deteksi:
  - multi login
  - idle

---

## Security

- Validasi:
  - attempt_id
  - ownership
  - state ujian

---

## Performa

- Hindari N+1 query
- Gunakan eager loading
- Cache data penting

---

## Warning

Sistem ini kritikal:
- Data loss = fatal
- Timer error = fatal
