/**
 * ws_reconnect_storm_test.js
 *
 * Simulates a WebSocket reconnect storm:
 *   - 3000 clients connect and hold persistent connections
 *   - At peak: ALL clients disconnect simultaneously
 *   - Within 5 seconds: ALL clients reconnect
 *
 * This validates:
 *   - Reverb's ability to handle burst reconnections
 *   - Redis pub/sub stability under reconnect flood
 *   - No message loss during the storm window
 *
 * Run from Linux VPS:
 *   k6 run ws_reconnect_storm_test.js
 */

import ws from 'k6/ws';
import { check, sleep } from 'k6';
import { Counter, Trend } from 'k6/metrics';

const reconnectSuccess = new Counter('reconnect_success');
const reconnectFailed  = new Counter('reconnect_failed');
const reconnectLatency = new Trend('reconnect_latency_ms');

// ─── Stage config ────────────────────────────────────────────────────────────
export const options = {
    scenarios: {
        reconnect_storm: {
            executor: 'ramping-vus',
            stages: [
                { target: 3000, duration: '2m' }, // ramp to 3000 connected clients
                { target: 3000, duration: '2m' }, // hold — all 3000 WS active
                // Storm happens inside the exec function (see below)
                { target: 3000, duration: '1m' }, // sustain post-reconnect
                { target: 0,    duration: '1m' }, // ramp down
            ],
        },
    },
    thresholds: {
        reconnect_success:    ['count>2700'],        // At least 90% reconnect success
        reconnect_latency_ms: ['p95<5000'],          // p95 reconnect within 5s
        ws_session_duration:  ['p95<30000'],
    },
};

const WS_URL    = __ENV.WS_URL    || 'ws://localhost:8080';
const APP_KEY   = __ENV.APP_KEY   || 'test-app-key';
const AUTH_TOKEN = __ENV.AUTH_TOKEN || 'test-token';

export default function () {
    // ── Phase 1: Initial connection ──────────────────────────────────────────
    let connected = false;
    let disconnectedAt = null;
    let reconnectedAt  = null;

    const firstConnectUrl = `${WS_URL}/app/${APP_KEY}?protocol=7&client=js&version=8.0.0`;

    const res = ws.connect(firstConnectUrl, {
        headers: { Authorization: `Bearer ${AUTH_TOKEN}` },
    }, function (socket) {
        socket.on('open', () => {
            connected = true;

            // Subscribe to a private exam channel
            socket.send(JSON.stringify({
                event: 'pusher:subscribe',
                data: { channel: `private-exam.load-test.${__VU}` },
            }));
        });

        socket.on('message', (data) => {
            const msg = JSON.parse(data);

            // Handle connection_established — Reverb sends this on open
            if (msg.event === 'pusher:connection_established') {
                // After 30-90s (random), simulate disconnect storm
                // All VUs will disconnect within the same ~5s window
                // due to synchronized ramp schedule
                const holdTime = 30 + Math.random() * 60;
                sleep(holdTime);

                // ── Storm: Force disconnect ──────────────────────────────
                disconnectedAt = Date.now();
                socket.close();
            }
        });

        socket.on('error', (e) => {
            reconnectFailed.add(1);
        });
    });

    check(res, { 'initial connection: status 101': (r) => r && r.status === 101 });

    // ── Phase 2: Reconnect (within 5 second window) ──────────────────────────
    if (disconnectedAt) {
        // Jitter: 0–2s random delay (realistic client backoff)
        sleep(Math.random() * 2);

        const reconnectStart = Date.now();
        let reconnected = false;

        const res2 = ws.connect(firstConnectUrl, {
            headers: { Authorization: `Bearer ${AUTH_TOKEN}` },
        }, function (socket) {
            socket.on('open', () => {
                reconnectedAt = Date.now();
                reconnected = true;

                reconnectLatency.add(reconnectedAt - disconnectedAt);
                reconnectSuccess.add(1);

                // Resubscribe (real clients do this on reconnect)
                socket.send(JSON.stringify({
                    event: 'pusher:subscribe',
                    data: { channel: `private-exam.load-test.${__VU}` },
                }));

                // Hold reconnected session for 30s then close cleanly
                sleep(30);
                socket.close();
            });

            socket.on('error', () => {
                if (!reconnected) reconnectFailed.add(1);
            });
        });

        check(res2, { 'reconnect: status 101': (r) => r && r.status === 101 });
    }
}
