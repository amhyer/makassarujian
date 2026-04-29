#!/bin/bash

# ==============================================================================
# AUTO ROLLBACK SCRIPT FOR MAKASSAR UJIAN
# ==============================================================================

APP_DIR="/var/www/makassarujian"
CURRENT_DIR="$APP_DIR/current"
RELEASES_DIR="$APP_DIR/releases"
NEW_RELEASE_ID=$1
HEALTH_ENDPOINT="http://localhost/health"

echo "🔍 Starting post-deployment health monitoring for release: $NEW_RELEASE_ID"

# Wait for Octane/Nginx to fully stabilize
sleep 10

ERROR_COUNT=0
MAX_ERRORS=3

for i in {1..12}; do
    # Check health endpoint, with a timeout of 2 seconds
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 2 $HEALTH_ENDPOINT || echo "TIMEOUT")

    echo "Health check #$i: HTTP $HTTP_STATUS"

    if [ "$HTTP_STATUS" != "200" ]; then
        ERROR_COUNT=$((ERROR_COUNT + 1))
        echo "⚠️ Health check failed! ($ERROR_COUNT/$MAX_ERRORS)"
    else
        # Success streak - reset errors
        ERROR_COUNT=0
    fi

    if [ $ERROR_COUNT -ge $MAX_ERRORS ]; then
        echo "🚨 CRITICAL: Health check failed $MAX_ERRORS times. Initiating Auto-Rollback!"
        
        # Find the previous release
        PREV_RELEASE=$(ls -1 $RELEASES_DIR | grep -v $NEW_RELEASE_ID | tail -n 1)
        
        if [ -z "$PREV_RELEASE" ]; then
            echo "❌ No previous release found to rollback to! Manual intervention required."
            exit 1
        fi

        echo "⏪ Rolling back to release: $PREV_RELEASE"
        
        # 1. Pause queue
        cd "$CURRENT_DIR"
        php artisan horizon:pause

        # 2. Switch symlink back
        cd "$APP_DIR"
        ln -sfn "$RELEASES_DIR/$PREV_RELEASE" current
        
        # 3. Reload Nginx and Octane
        sudo systemctl reload nginx
        cd "$CURRENT_DIR"
        php artisan octane:reload
        
        # 4. Resume queue on old code
        php artisan horizon:continue
        php artisan horizon:terminate

        echo "✅ Rollback completed successfully."
        exit 1
    fi

    sleep 5
done

echo "🎉 Deployment stabilized! No rollback required."
exit 0
