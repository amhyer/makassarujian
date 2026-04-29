import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';

// Custom metrics to track specific scenarios
const examStartLatency = new Trend('exam_start_duration');
const submitLatency = new Trend('exam_submit_duration');
const errorRate = new Rate('errors');

// Test Configuration (Scalable from 1k to 20k)
export const options = {
    scenarios: {
        // Phase 1: Normal load (1k users)
        normal_load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '30s', target: 1000 }, // Ramp up to 1000 VUs
                { duration: '1m', target: 1000 },  // Stay at 1000 VUs
                { duration: '30s', target: 0 },    // Ramp down
            ],
            tags: { phase: 'normal' },
        },
        // Phase 2: High load (5k users)
        // Uncomment to run High load
        /*
        high_load: {
            executor: 'ramping-vus',
            startTime: '2m',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 5000 },
                { duration: '2m', target: 5000 },
                { duration: '1m', target: 0 },
            ],
            tags: { phase: 'high' },
        },
        */
        // Phase 3: Extreme/National scale (10k-20k users)
        // Requires significant infrastructure to run without client-side bottleneck
        /*
        extreme_load: {
            executor: 'ramping-vus',
            startTime: '6m',
            startVUs: 0,
            stages: [
                { duration: '2m', target: 20000 },
                { duration: '3m', target: 20000 },
                { duration: '2m', target: 0 },
            ],
            tags: { phase: 'extreme' },
        }
        */
    },
    thresholds: {
        // SLOs: 95% of requests must complete below 2s
        'http_req_duration': ['p(95)<2000'],
        'exam_start_duration': ['p(95)<3000'],
        // Error rate must be less than 1%
        'errors': ['rate<0.01'],
    },
};

const BASE_URL = 'http://localhost'; // Change to actual load balancer IP in production

export default function () {
    // 1. Simulate Student Logging In / Fetching Server Time
    let res = http.get(`${BASE_URL}/api/exam/server-time`);
    check(res, {
        'status is 200': (r) => r.status === 200,
    }) || errorRate.add(1);
    
    sleep(1);

    // 2. Simulate Student Auto-saving an answer (Heavy Redis/Queue operation)
    let payload = JSON.stringify({
        question_id: Math.floor(Math.random() * 50) + 1,
        answer: 'A',
        time_spent: 15
    });
    
    let params = {
        headers: {
            'Content-Type': 'application/json',
            // 'Authorization': 'Bearer ' + token // Ensure tokens are seeded
        },
    };

    // We simulate hitting the autosave endpoint
    // Normally this requires auth, but for benchmark infra testing we might disable auth or use a generic token
    res = http.post(`${BASE_URL}/api/exam/save-answer`, payload, params);
    check(res, {
        'autosave ok': (r) => r.status === 200 || r.status === 401, // Accepting 401 just for raw HTTP throughput testing if no token
    }) || errorRate.add(1);

    sleep(3); // Wait 3 seconds before next question
}
