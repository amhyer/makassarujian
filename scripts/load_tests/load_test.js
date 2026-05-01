import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
    stages: [
        { duration: '1m', target: 2000 }, // Ramp up to 2000 users
        { duration: '1m', target: 5000 }, // Ramp up to 5000 users
        { duration: '2m', target: 5000 }, // Stay at 5000 users
        { duration: '1m', target: 10000 }, // Spike to 10000 users
        { duration: '3m', target: 10000 }, // Stay at 10000 users
        { duration: '1m', target: 0 },    // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95% of requests must be below 500ms
        http_req_failed: ['rate<0.01'],   // Error rate should be less than 1%
    },
};

const BASE_URL = 'http://localhost:8000/api';
const EXAM_ID = 'your-exam-uuid-here'; // Replace with real ID
const AUTH_TOKEN = 'your-token-here'; // Replace with real student token

export default function () {
    const params = {
        headers: {
            'Authorization': `Bearer ${AUTH_TOKEN}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    // 1. Check Session/Timer (Simulate 30s sync)
    let sessionRes = http.get(`${BASE_URL}/exam/session`, params);
    check(sessionRes, { 'session status is 200': (r) => r.status === 200 });

    // 2. Autosave Answer (Simulate 5s interval)
    let payload = JSON.stringify({
        attempt_id: 'your-attempt-uuid',
        question_id: Math.floor(Math.random() * 50) + 1,
        selected_option: 'option_a',
    });

    let saveRes = http.post(`${BASE_URL}/exam/save-answer`, payload, params);
    check(saveRes, { 'save answer is 200': (r) => r.status === 200 });

    sleep(5); // Simulate thinking/interval time
}
