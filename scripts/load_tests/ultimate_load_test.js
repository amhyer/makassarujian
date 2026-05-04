import http from 'k6/http';
import ws from 'k6/ws';
import { check } from 'k6';
import { SharedArray } from 'k6/data';
import { Rate, Trend, Counter } from 'k6/metrics';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

// Custom Metrics
const redisOpsSuccessRate = new Rate('redis_ops_success_rate');
const saveAnswerTrend = new Trend('save_answer_duration');
const cheatLogRate = new Rate('cheat_log_success_rate');
const wsThroughput = new Counter('ws_throughput_msgs_per_sec');

// 1. DATA GENERATOR BINDING
const users = new SharedArray('users', function () {
    const fileData = open('./tests/LoadTest/users.csv');
    const parsed = papaparse.parse(fileData, { header: true, skipEmptyLines: true });
    return parsed.data;
});

// 2. STAGES CONFIGURATION (Ramping up to 10k)
export let options = {
    stages: [
        { duration: '2m', target: 2000 },  // ramp-up 0 → 2000
        { duration: '3m', target: 5000 },  // 2000 → 5000
        { duration: '5m', target: 10000 }, // 5000 → 10000
        { duration: '10m', target: 10000 }, // sustain 10k
        { duration: '3m', target: 0 },     // ramp-down
    ],
    // 6. TARGET THRESHOLDS
    thresholds: {
        http_req_duration: ['p(95)<1500', 'p(99)<3000'], // p99 < 3s tighter threshold
        http_req_failed: ['rate<0.01'],    // error < 1%
        ws_sessions: ['count>0'],
        redis_ops_success_rate: ['rate>0.99'],
        cheat_log_success_rate: ['rate>0.95']
    },
};

const BASE_URL = 'http://localhost:8000/api';
const REVERB_APP_KEY = 'qsyzlxvwj83rxhzfxsv0';
const WS_URL = `ws://127.0.0.1:8080/app/${REVERB_APP_KEY}?protocol=7&client=js&version=8.3.0&flash=false`;

// Helper: Random Integer for Jitter & Think Time
function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

export default function () {
    // Prevent array out-of-bounds if VUs > CSV rows
    const user = users[(__VU - 1) % users.length];

    const params = {
        headers: {
            'Authorization': `Bearer ${user.token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            // FAILURE INJECTION HEADERS
            'X-Inject-Redis-Down': __ENV.REDIS_DOWN === 'true' ? 'true' : 'false',
            'X-Device-Fingerprint': 'k6-load-test-' + __VU,
        },
    };

    // 4. WEBSOCKET SIMULATION (Persistent & Reconnect)
    const res = ws.connect(WS_URL, {}, function (socket) {
        
        socket.on('open', () => {
            // ...
        });

        socket.on('message', (msg) => {
            wsThroughput.add(1);
            try {
                const data = JSON.parse(msg);
                if (data.event === 'pusher:connection_established') {
                    // Subscribe to exam channel
                    socket.send(JSON.stringify({
                        event: 'pusher:subscribe',
                        data: { channel: `presence-exam.${user.exam_id}` }
                    }));
                }
            } catch (e) { }
        });

        // Keep WS alive
        socket.setInterval(function timeout() {
            socket.send(JSON.stringify({ event: 'pusher:ping', data: {} }));
        }, 30000);

        // 3. AUTOSAVE PATTERN
        // Interval 5 detik + Random Jitter
        socket.setInterval(function () {
            let payload = JSON.stringify({
                attempt_id: user.attempt_id,
                question_id: getRandomInt(1, 50),
                selected_option: 'option_' + getRandomInt(1, 4),
            });

            let saveRes = http.post(`${BASE_URL}/exam/save-answer`, payload, params);
            
            saveAnswerTrend.add(saveRes.timings.duration);
            
            let isSuccess = saveRes.status === 200;
            redisOpsSuccessRate.add(isSuccess);

            check(saveRes, { 
                'save answer status 200': (r) => r.status === 200 
            });

        }, 5000 + getRandomInt(-1000, 1000)); // 5s interval + jitter

        // 5. CHEAT LOG API FLOOD (Simulate 1 in 5 users cheating randomly)
        if (__VU % 5 === 0) {
            socket.setInterval(function () {
                let cheatPayload = JSON.stringify({
                    attempt_id: user.attempt_id,
                    type: 'focus_loss'
                });

                let cheatRes = http.post(`${BASE_URL}/exam/cheat-log`, cheatPayload, params);
                cheatLogRate.add(cheatRes.status === 200);

            }, 15000 + getRandomInt(-2000, 2000)); // Every ~15s
        }

        // Manually close socket after max scenario time (23 minutes total)
        socket.setTimeout(function () {
            socket.close();
        }, 1380000); 

        socket.on('close', () => {
            // Reconnect logic in k6 is handled by the next VU iteration
        });
    });

    check(res, { 'WS connected successfully': (r) => r && r.status === 101 });
}
