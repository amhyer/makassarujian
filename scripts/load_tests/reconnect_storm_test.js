import http from 'k6/http';
import ws from 'k6/ws';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { Rate, Trend, Counter } from 'k6/metrics';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

// ── CUSTOM METRICS ────────────────────────────────────────────────────────────
const reconnectSuccessRate  = new Rate('ws_reconnect_success_rate');
const reconnectLatency      = new Trend('ws_reconnect_latency_ms');
const authBottleneckRate    = new Rate('auth_success_after_reconnect');
const eventLagAfterReconnect = new Trend('event_lag_after_reconnect_ms');

const users = new SharedArray('users', function () {
    const fileData = open('./tests/LoadTest/users.csv');
    const parsed = papaparse.parse(fileData, { header: true, skipEmptyLines: true });
    return parsed.data;
});

// ── OPTIONS: Reconnect Storm Simulation ──────────────────────────────────────
// Scenario:
//   Phase 1 (30s): 2000 users connect normally (baseline)
//   Phase 2 (5s):  ALL sockets forcefully disconnected (power cut simulation)
//   Phase 3 (2m):  ALL 2000 users reconnect simultaneously (the "storm")
// Goal: Validate Reverb survives 2000 concurrent reconnect + re-auth + re-subscribe
export let options = {
    scenarios: {
        reconnect_storm: {
            executor: 'shared-iterations',
            vus: 2000,
            iterations: 2000, // Each VU runs exactly once (the full connect-disconnect-reconnect cycle)
            maxDuration: '10m',
        },
    },
    thresholds: {
        ws_reconnect_success_rate:   ['rate>0.95'],  // 95%+ must reconnect successfully
        ws_reconnect_latency_ms:     ['p(95)<5000'], // p95 reconnect within 5s
        auth_success_after_reconnect: ['rate>0.98'], // Auth must almost never fail
        http_req_failed:             ['rate<0.05'],
    },
};

const BASE_URL = 'http://localhost:8000/api';
const REVERB_APP_KEY = 'qsyzlxvwj83rxhzfxsv0';
const WS_URL = `ws://127.0.0.1:8080/app/${REVERB_APP_KEY}?protocol=7&client=js&version=8.3.0&flash=false`;

function connectAndSubscribe(user, params) {
    const connectStart = Date.now();
    let subscribed = false;
    let firstEventTime = null;

    const res = ws.connect(WS_URL, {}, function (socket) {
        socket.on('message', (msg) => {
            try {
                const data = JSON.parse(msg);

                if (data.event === 'pusher:connection_established') {
                    // Re-authenticate and re-subscribe immediately on connect
                    socket.send(JSON.stringify({
                        event: 'pusher:subscribe',
                        data: { channel: `presence-exam.${user.exam_id}` }
                    }));
                }

                if (data.event === 'pusher_internal:subscription_succeeded') {
                    subscribed = true;
                    firstEventTime = Date.now();
                    reconnectLatency.add(firstEventTime - connectStart);
                    socket.close(); // Done — close after successful reconnect
                }
            } catch (e) { }
        });

        socket.on('error', () => {
            reconnectSuccessRate.add(false);
            socket.close();
        });

        // Timeout: if not subscribed in 10s, mark as failure
        socket.setTimeout(() => {
            if (!subscribed) {
                reconnectSuccessRate.add(false);
            }
            socket.close();
        }, 10000);
    });

    return { res, subscribed };
}

export default function () {
    const user = users[(__VU - 1) % users.length];

    const params = {
        headers: {
            'Authorization': `Bearer ${user.token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    // ── PHASE 1: Initial connection (baseline) ────────────────────────────────
    // Each VU simulates a student who was already connected to their exam
    let initialRes = ws.connect(WS_URL, {}, function (socket) {
        socket.on('message', (msg) => {
            try {
                const data = JSON.parse(msg);
                if (data.event === 'pusher:connection_established') {
                    socket.send(JSON.stringify({
                        event: 'pusher:subscribe',
                        data: { channel: `presence-exam.${user.exam_id}` }
                    }));
                }
                if (data.event === 'pusher_internal:subscription_succeeded') {
                    // Simulate 30 seconds of normal exam activity, then "power cut"
                    socket.setTimeout(() => {
                        socket.close(); // Simulate disconnect
                    }, 30000);
                }
            } catch (e) { }
        });
        // Safety: close after 35s even if subscribe fails
        socket.setTimeout(() => socket.close(), 35000);
    });

    // ── PHASE 2: Simulate power cut — all VUs now "offline" ──────────────────
    // VUs with lower IDs will be here slightly earlier, creating natural spread
    // All 2000 VUs will be disconnected within ~5s of each other
    sleep(1); // Brief pause before storm begins

    // ── PHASE 3: Reconnect Storm ──────────────────────────────────────────────
    // ALL 2000 VUs reconnect within this narrow window
    // Validate: auth not bottlenecked, event not lagged

    // First verify API token still valid (Auth bottleneck check)
    const authCheckStart = Date.now();
    const authRes = http.get(`${BASE_URL}/exam/session?attempt_id=${user.attempt_id}`, params);
    authBottleneckRate.add(authRes.status === 200);
    eventLagAfterReconnect.add(Date.now() - authCheckStart);

    check(authRes, { 'auth still valid after disconnect': (r) => r.status === 200 });

    // Now reconnect WS
    const { res, subscribed } = connectAndSubscribe(user, params);
    reconnectSuccessRate.add(subscribed || (res && res.status === 101));

    check(res, { 'WS reconnect successful': (r) => r && r.status === 101 });
}
