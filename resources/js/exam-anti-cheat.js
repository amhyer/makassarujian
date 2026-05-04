// resources/js/exam-anti-cheat.js

window.ExamAntiCheat = function(attemptId, token) {
    let isRequesting = false;
    let lastRequestTime = 0;
    const RATE_LIMIT_MS = 2000; // max 1 log per 2 seconds

    // 1. Device Fingerprint Generator
    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = "top";
            ctx.font = "14px 'Arial'";
            ctx.textBaseline = "alphabetic";
            ctx.fillStyle = "#f60";
            ctx.fillRect(125,1,62,20);
            ctx.fillStyle = "#069";
            ctx.fillText("MakassarUjian AntiCheat, \ud83d\ude03", 2, 15);
            ctx.fillStyle = "rgba(102, 204, 0, 0.7)";
            ctx.fillText("MakassarUjian AntiCheat, \ud83d\ude03", 4, 17);
            
            const b64 = canvas.toDataURL();
            // Simple hash
            let hash = 0;
            for (let i = 0; i < b64.length; i++) {
                const char = b64.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return Math.abs(hash).toString(16);
        } catch (e) {
            return 'unknown_canvas';
        }
    }

    const fingerprint = getCanvasFingerprint();

    // 2. Logging Function
    function logCheat(type) {
        if (isRequesting) return;
        
        const now = Date.now();
        if (now - lastRequestTime < RATE_LIMIT_MS) return;

        isRequesting = true;
        lastRequestTime = now;

        fetch('/api/exam/cheat-log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Device-Fingerprint': fingerprint
            },
            body: JSON.stringify({
                attempt_id: attemptId,
                type: type
            })
        })
        .then(res => res.json())
        .catch(err => console.error('AntiCheat sync error', err))
        .finally(() => {
            isRequesting = false;
        });
    }

    // 3. Event Listeners
    // Focus Loss (Tab Switch or minimizing)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            logCheat('focus_loss');
            alert("PERINGATAN: Anda terdeteksi keluar dari halaman ujian. Aktivitas ini dicatat oleh sistem.");
        }
    });

    window.addEventListener('blur', () => {
        logCheat('focus_loss');
    });

    // Fullscreen detection
    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            logCheat('exit_fullscreen');
            alert("PERINGATAN: Anda keluar dari mode layar penuh. Aktivitas ini dicatat oleh sistem.");
        }
    });

    return {
        fingerprint: fingerprint,
        enterFullscreen: function() {
            const el = document.documentElement;
            if (el.requestFullscreen) {
                el.requestFullscreen().catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message} (${err.name})`);
                });
            }
        }
    };
};
