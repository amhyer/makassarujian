import ws from 'k6/ws';
import { check } from 'k6';

export let options = {
    stages: [
        { duration: '30s', target: 2000 }, // Ramp up to 2,000 connections
        { duration: '1m', target: 5000 },  // Ramp up to 5,000 connections
        { duration: '2m', target: 5000 },  // Hold 5,000 connections for stability
        { duration: '30s', target: 0 },    // Ramp down to 0
    ],
};

const appKey = 'qsyzlxvwj83rxhzfxsv0'; // Reverb App Key from .env
const url = `ws://127.0.0.1:8080/app/${appKey}?protocol=7&client=js&version=8.3.0&flash=false`;

export default function () {
    const res = ws.connect(url, {}, function (socket) {
        
        socket.on('open', function () {
            // Connection opened
        });

        socket.on('message', function (msg) {
            try {
                const data = JSON.parse(msg);
                
                // When connected, optionally subscribe to a channel
                if (data.event === 'pusher:connection_established') {
                    socket.send(JSON.stringify({
                        event: 'pusher:subscribe',
                        data: {
                            channel: 'public-exam-test' // Using a public channel for pure load testing
                        }
                    }));
                }
            } catch (e) {
                // Ignore parsing errors for non-JSON messages (if any)
            }
        });

        // Ping the server every 30 seconds to keep the connection alive
        // Reverb ping interval is set to 60s in .env, so 30s is safe
        socket.setInterval(function timeout() {
            socket.send(JSON.stringify({
                event: 'pusher:ping',
                data: {}
            }));
        }, 30000);

        // Keep the connection open for the duration of the VU's life (approx 4 minutes max)
        socket.setTimeout(function () {
            socket.close();
        }, 240000); 

        socket.on('close', function () {
            // Connection closed
        });
    });

    check(res, { 'status is 101': (r) => r && r.status === 101 });
}
