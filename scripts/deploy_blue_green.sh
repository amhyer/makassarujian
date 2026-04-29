#!/bin/bash

# ==============================================================================
# BLUE-GREEN DEPLOYMENT SCRIPT FOR MAKASSAR UJIAN
# ==============================================================================

set -e

# Configuration
APP_DIR="/var/www/makassarujian"
RELEASES_DIR="$APP_DIR/releases"
CURRENT_DIR="$APP_DIR/current"
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
NEW_RELEASE_DIR="$RELEASES_DIR/$TIMESTAMP"

echo "🚀 Starting Blue-Green Deployment: $TIMESTAMP"

# 1. Prepare New Environment (Green)
mkdir -p "$NEW_RELEASE_DIR"
git clone --depth 1 git@github.com:your-repo/makassarujian.git "$NEW_RELEASE_DIR"

cd "$NEW_RELEASE_DIR"

# Link shared `.env` and storage
ln -s "$APP_DIR/.env" "$NEW_RELEASE_DIR/.env"
ln -s "$APP_DIR/storage/app" "$NEW_RELEASE_DIR/storage/app"

echo "📦 Installing Dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
npm ci && npm run build

echo "🗄️ Running Backward-Compatible Migrations..."
php artisan migrate --force

# 2. Queue Safety (Pause current active Horizon)
echo "⏸️ Pausing current queue processing (Zero Job Loss)..."
if [ -d "$CURRENT_DIR" ]; then
    cd "$CURRENT_DIR"
    php artisan horizon:pause
fi

# 3. Switch Traffic (Symlink to new release)
echo "🔄 Switching Traffic to New Release..."
cd "$APP_DIR"
ln -sfn "$NEW_RELEASE_DIR" current

# 4. Graceful Reload Web Server (Nginx)
echo "🌐 Reloading Nginx..."
sudo systemctl reload nginx
# Note: For Octane (RoadRunner/Swoole), you need to reload Octane concurrently or via LB.
# In a pure Octane setup, we reload Octane workers gracefully.
cd "$CURRENT_DIR"
php artisan octane:reload

# 5. Resume Queues
echo "▶️ Resuming Queue processing on new code..."
php artisan horizon:continue
php artisan horizon:terminate # Forces horizon to restart and pick up new code

# 6. WebSocket (Reverb) Rolling Restart
# Instead of restarting the daemon immediately which drops all clients,
# we gracefully restart reverb instances one by one.
echo "📡 Initiating WebSocket (Reverb) Rolling Restart..."
# Reverb handles zero downtime via restart command in supervisor or systemd
php artisan reverb:restart

echo "✅ Deployment Successful!"

# 7. Trigger Health Monitor & Auto-Rollback Script
nohup bash "$CURRENT_DIR/scripts/health_monitor_rollback.sh" "$TIMESTAMP" > /dev/null 2>&1 &
