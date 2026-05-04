import http from 'k6/http';
import ws from 'k6/ws';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { Gauge, Rate, Trend } from 'k6/metrics';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

// ── CUSTOM METRICS ────────────────────────────────────────────────────────────
const memoryDegradation  = new Trend('memory_degradation_proxy_rtt'); // RTT as memory proxy
const wsDisconnectRate   = new Rate('ws_disconnect_rate');
const autosaveSuccessRate = new Rate('autosave_success_rate');

// ── SHARED DATA ───────────────────────────────────────────────────────────────
const users = new SharedArray('users', function () {
    const fileData = open('./tests/LoadTest/users.csv');
    const parsed = papaparse.parse(fileData, { header: true, skipEmptyLines: true });
    return parsed.data;
});

// ── OPTIONS: Long-Running Endurance Test (2 Hours) ───────────────────────────
// Simulates 5000 students taking a real 120-minute exam.
// Purpose: detect memory leaks, Redis fragmentation, WS connection drift.
export let options = {
    scenarios: {
        long_running_exam: {
            executor: 'constant-vus',
            vus: 5000,
            duration: '120m', // Full 2-hour exam duration
        },
    },
    thresholds: {
        // Memory leak signature: p99 RTT should NOT creep up over time
        memory_degradation_proxy_rtt: [
            'p(95)<1500',
            'p(99)<3000',
        ],
        // Autosave must remain reliable for the full 2 hours
        autosave_success_rate: ['rate>0.98'],
        // WS disconnect rate should be < 2% (not > 2% sustained)
        ws_disconnect_rate: ['rate<0.02'],
        http_req_failed: ['rate<0.02'],
    },
};

const BASE_URL = 'http://localhost:8000/api';
const REVERB_APP_KEY = 'qsyzlxvwj83rxhzfxsv0';
const WS_URL = `ws://127.0.0.1:8080/app/${REVERB_APP_KEY}?protocol=7&client=js&version=8.3.0&flash=false`;

function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

export default function () {
    const user = users[(__VU - 1) % users.length];

    const params = {
        headers: {
            'Authorization': `Bearer ${user.token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Device-Fingerprint': 'endurance-test-' + __VU,
        },
    };

    // ── WEBSOCKET: Connect and hold for full exam duration ────────────────────
    const wsRes = ws.connect(WS_URL, {}, function (socket) {
        let connected = true;

        socket.on('open', () => {
            // Subscribe to exam channel on connect
            socket.send(JSON.stringify({
                event: 'pusher:subscribe',
                data: { channel: `presence-exam.${user.exam_id}` }
            }));
        });

        socket.on('close', () => {
            connected = false;
            wsDisconnectRate.add(1); // Record as disconnect event
        });

        socket.on('error', () => {
            connected = false;
            wsDisconnectRate.add(1);
        });

        // ── HEARTBEAT: Keep WS alive every 30s ───────────────────────────────
        socket.setInterval(function () {
            if (connected) {
                socket.send(JSON.stringify({ event: 'pusher:ping', data: {} }));
                wsDisconnectRate.add(0); // Record as "alive" (not disconnected)
            }
        }, 30000);

        // ── AUTOSAVE: Every 5s ± jitter — simulates real exam behavior ────────
        socket.setInterval(function () {
            const start = Date.now();

            let payload = JSON.stringify({
                attempt_id:      user.attempt_id,
                question_id:     getRandomInt(1, 50),
                selected_option: 'option_' + getRandomInt(1, 4),
            });

            let saveRes = http.post(`${BASE_URL}/exam/save-answer`, payload, params);
            const rtt = Date.now() - start;

            memoryDegradation.add(rtt); // Track RTT as memory degradation proxy
            autosaveSuccessRate.add(saveRes.status === 200);

            check(saveRes, { 'autosave 200': (r) => r.status === 200 });

        }, 5000 + getRandomInt(-500, 500));

        // ── SESSION TIMER CHECK: Every 60s ───────────────────────────────────
        // Simulates frontend polling timer endpoint
        socket.setInterval(function () {
            let timerRes = http.get(`${BASE_URL}/exam/session?attempt_id=${user.attempt_id}`, params);
            check(timerRes, { 'timer endpoint 200': (r) => r.status === 200 });
        }, 60000);

        // Close WS after 121 minutes (slightly after exam ends)
        socket.setTimeout(function () {
            socket.close();
        }, 121 * 60 * 1000);
    });

    check(wsRes, { 'WS connected': (r) => r && r.status === 101 });
}
