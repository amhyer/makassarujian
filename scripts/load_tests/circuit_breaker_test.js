import http from 'k6/http';
import { check } from 'k6';
import { SharedArray } from 'k6/data';
import { Rate, Trend } from 'k6/metrics';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

// Custom Metrics
const redisOpsSuccessRate = new Rate('redis_ops_success_rate');
const saveAnswerTrend = new Trend('save_answer_duration');

// 1. DATA GENERATOR BINDING
const users = new SharedArray('users', function () {
    const fileData = open('./tests/LoadTest/users.csv');
    const parsed = papaparse.parse(fileData, { header: true, skipEmptyLines: true });
    return parsed.data;
});

// 2. STAGES CONFIGURATION (Circuit Breaker Scenario)
export let options = {
    stages: [
        { duration: '30s', target: 50 },  // Phase 1: Baseline (Normal Traffic)
        { duration: '1m', target: 50 },   // Phase 2: Inject Redis Failure (Circuit Breaker OPEN)
        { duration: '30s', target: 50 },  // Phase 3: Recovery (Redis UP, Circuit Breaker CLOSE)
    ],
    thresholds: {
        http_req_failed: ['rate<0.05'], // Allow up to 5% failures during transitions
        save_answer_duration: ['p(95)<2000'], // DB Fallback should still be reasonably fast
    },
};

const BASE_URL = 'http://localhost:8000/api';

// Helper: Random Integer
function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

export default function () {
    const user = users[(__VU - 1) % users.length];
    
    // Determine if we should inject Redis failure based on current execution time
    // Total duration is 2 minutes (120s). Fail between 30s and 90s.
    const currentTimeSec = (__ITER * 5) + (__VU * 0.1); // Rough approximation for testing
    // A better way is using execution time or just relying on an env var to toggle manually.
    // For this automated test, we will use __ENV to inject randomly or specifically.
    
    // Using k6 execution context is complex without execution module, so let's simulate 
    // based on scenarios or just randomly failing 50% of the time to test circuit breaker resilience.
    // Actually, we can use the __ENV variable if we run it via a shell script, OR
    // we can just use the VU iteration count to toggle. Let's make every 3rd request fail.
    
    const injectFailure = (__ITER % 3 === 0);

    const params = {
        headers: {
            'Authorization': `Bearer ${user.token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Inject-Redis-Down': injectFailure ? 'true' : 'false',
            'X-Device-Fingerprint': 'k6-circuit-test',
        },
    };

    let payload = JSON.stringify({
        attempt_id: user.attempt_id,
        question_id: getRandomInt(1, 50),
        selected_option: 'option_' + getRandomInt(1, 4),
    });

    let saveRes = http.post(`${BASE_URL}/exam/save-answer`, payload, params);
    
    saveAnswerTrend.add(saveRes.timings.duration);
    
    // Check if SafeMode kicked in
    let safeModeActive = false;
    try {
        let body = saveRes.json();
        safeModeActive = body.safe_mode === true;
    } catch(e) {}

    redisOpsSuccessRate.add(saveRes.status === 200 && !safeModeActive);

    check(saveRes, { 
        'save answer status 200': (r) => r.status === 200,
        'safe mode activated when injected': (r) => injectFailure ? safeModeActive : true
    });
}
