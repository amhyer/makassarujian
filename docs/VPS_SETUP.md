# ============================================================
# VPS Production Setup Checklist
# Makassar Ujian — Target: 10.000 concurrent exam users
# ============================================================
# Rekomendasi spesifikasi minimum:
#   Provider  : DigitalOcean / Vultr / Biznet / IDCloudHost
#   Region    : Singapore (SGP1) atau Jakarta
#   Plan      : 8 vCPU, 16GB RAM, 200GB SSD NVMe
#   OS        : Ubuntu 22.04 LTS
# ============================================================

## 1. OS-Level Tuning (WAJIB — tanpa ini port exhaustion terjadi)

```bash
# /etc/sysctl.d/99-makassarujian.conf

# Increase max open files
fs.file-max = 2097152

# Increase local port range (default ~28k → 60k)
net.ipv4.ip_local_port_range = 1024 65535

# Enable TCP Fast Open
net.ipv4.tcp_fastopen = 3

# Reduce TIME_WAIT socket reuse
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15

# Increase backlog for accept queue
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 65535

# Increase socket buffer sizes
net.core.rmem_max = 134217728
net.core.wmem_max = 134217728
net.ipv4.tcp_rmem = 4096 87380 67108864
net.ipv4.tcp_wmem = 4096 65536 67108864

# Apply:
sysctl -p /etc/sysctl.d/99-makassarujian.conf
```

## 2. ulimit (Open Files per Process)

```bash
# /etc/security/limits.conf
www-data soft nofile 65535
www-data hard nofile 65535
root     soft nofile 65535
root     hard nofile 65535

# Verify after relogin:
ulimit -n   # should be 65535
```

## 3. Nginx Setup

```bash
apt install nginx
cp /var/www/makassarujian/current/docker/nginx/nginx.production.conf /etc/nginx/nginx.conf
nginx -t
systemctl reload nginx
```

## 4. PHP & Octane

```bash
apt install php8.3-fpm php8.3-cli php8.3-redis php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl
composer install --no-dev --optimize-autoloader

# Install RoadRunner binary
./vendor/bin/rr get-binary

# Start Octane (2 instances on different ports)
php artisan octane:start --server=roadrunner --port=8000 --workers=8 &
php artisan octane:start --server=roadrunner --port=8001 --workers=8 &
```

## 5. Reverb WebSocket Cluster

```bash
chmod +x scripts/start_reverb_cluster.sh
./scripts/start_reverb_cluster.sh start

# Verify all 4 ports listening:
ss -tlnp | grep -E "8010|8011|8012|8013"
```

## 6. Redis Tuning

```bash
# /etc/redis/redis.conf (critical settings)
maxmemory 4gb
maxmemory-policy allkeys-lru
tcp-backlog 511
hz 20
io-threads 4               # Use multiple I/O threads
io-threads-do-reads yes
```

## 7. PostgreSQL Tuning

```bash
# /etc/postgresql/16/main/postgresql.conf
max_connections = 200
shared_buffers = 4GB          # 25% of RAM
effective_cache_size = 12GB   # 75% of RAM
work_mem = 64MB
maintenance_work_mem = 1GB
wal_buffers = 64MB
checkpoint_completion_target = 0.9
```

## 8. Load Test dari VPS (bukan Windows!)

```bash
# Install k6 on a SEPARATE load generator VPS
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg \
    --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" \
    | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update && sudo apt-get install k6

# Run load test (10k users)
k6 run --vus 10000 --duration 20m \
    -e WS_URL=wss://ujian.makassar.go.id \
    -e AUTH_TOKEN=<student_token> \
    ultimate_load_test.js

# Run reconnect storm test
k6 run ws_reconnect_storm_test.js \
    -e WS_URL=wss://ujian.makassar.go.id \
    -e AUTH_TOKEN=<student_token>
```

## 9. Monitoring During Test

```bash
# Terminal 1 — system resources
htop

# Terminal 2 — open connections
watch -n 1 "ss -s | grep -E 'TCP|estab'"

# Terminal 3 — Nginx connections
watch -n 1 "nginx -V 2>&1 | head -1; curl -s http://localhost/nginx_status"

# Terminal 4 — Redis
redis-cli info stats | grep -E "connected_clients|total_commands|rejected"

# Grafana: http://your-vps:3000
# → Latency p95, Error rate, Redis ops/sec, Queue delay
```

## 10. Smoke Test Before Full Load

```bash
# Quick sanity check — 100 users for 30s
k6 run --vus 100 --duration 30s ultimate_load_test.js

# Check /health endpoint
curl https://ujian.makassar.go.id/health | python3 -m json.tool
```

## ✅ Acceptance Criteria

| Metric | Target |
|---|---|
| p95 response time | < 1.5 detik |
| Error rate | < 1% |
| Queue delay | < 5 detik |
| WS reconnect success | > 90% dalam 5 detik |
| Reconnect p95 latency | < 5 detik |
| Redis rejected connections | 0 |
