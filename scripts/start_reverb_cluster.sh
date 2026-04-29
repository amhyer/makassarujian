#!/bin/bash
# ============================================================
# scripts/start_reverb_cluster.sh
#
# Start multiple Laravel Reverb instances on separate ports.
#
# WHY:
#   Reverb runs a single-threaded event loop (like Node.js).
#   One instance = one CPU core. With 3000+ active WebSocket
#   connections, a single core becomes the bottleneck.
#
#   Solution: Run N instances (one per core), let Nginx
#   distribute connections via ip_hash (sticky sessions).
#   All instances share the same Redis pub/sub channel,
#   so broadcasts reach all connected clients regardless
#   of which instance they're on.
#
# USAGE:
#   chmod +x scripts/start_reverb_cluster.sh
#   ./scripts/start_reverb_cluster.sh start
#   ./scripts/start_reverb_cluster.sh stop
#   ./scripts/start_reverb_cluster.sh status
#
# PREREQUISITE:
#   - REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET in .env
#   - Redis running and configured in .env
#   - supervisor or screen (for production — use supervisor)
# ============================================================

APP_DIR="/var/www/makassarujian/current"
LOG_DIR="/var/www/makassarujian/logs/reverb"
PORTS=(8010 8011 8012 8013)   # One per CPU core — adjust to match nproc

mkdir -p "$LOG_DIR"

start_reverb() {
    echo "Starting Reverb cluster (${#PORTS[@]} instances)..."
    for PORT in "${PORTS[@]}"; do
        PID_FILE="/tmp/reverb_${PORT}.pid"
        LOG_FILE="${LOG_DIR}/reverb_${PORT}.log"

        if [ -f "$PID_FILE" ] && kill -0 "$(cat $PID_FILE)" 2>/dev/null; then
            echo "  [SKIP] Reverb on port $PORT already running (PID $(cat $PID_FILE))"
            continue
        fi

        nohup php "$APP_DIR/artisan" reverb:start \
            --port="$PORT" \
            --no-interaction \
            >> "$LOG_FILE" 2>&1 &

        echo $! > "$PID_FILE"
        echo "  [OK] Reverb started on port $PORT (PID $!)"
    done
    echo "Done. Nginx upstream should now distribute across all instances."
}

stop_reverb() {
    echo "Stopping Reverb cluster..."
    for PORT in "${PORTS[@]}"; do
        PID_FILE="/tmp/reverb_${PORT}.pid"
        if [ -f "$PID_FILE" ]; then
            PID=$(cat "$PID_FILE")
            if kill -0 "$PID" 2>/dev/null; then
                kill "$PID"
                echo "  [OK] Killed Reverb on port $PORT (PID $PID)"
            fi
            rm -f "$PID_FILE"
        else
            echo "  [SKIP] No PID file for port $PORT"
        fi
    done
}

status_reverb() {
    echo "Reverb cluster status:"
    for PORT in "${PORTS[@]}"; do
        PID_FILE="/tmp/reverb_${PORT}.pid"
        if [ -f "$PID_FILE" ] && kill -0 "$(cat $PID_FILE)" 2>/dev/null; then
            echo "  Port $PORT: RUNNING (PID $(cat $PID_FILE))"
        else
            echo "  Port $PORT: STOPPED"
        fi
    done
}

case "$1" in
    start)  start_reverb  ;;
    stop)   stop_reverb   ;;
    restart) stop_reverb; sleep 1; start_reverb ;;
    status) status_reverb ;;
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac
