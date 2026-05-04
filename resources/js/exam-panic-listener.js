/**
 * Exam Anti-Cheat + Panic Mode Listener
 *
 * Injects into the exam page to:
 *  1. Listen for PANIC broadcasts from the tenant channel (admin panic button)
 *  2. Overlay a fullscreen alert if panic activates
 *  3. Resume gracefully if panic deactivates
 */
window.ExamPanicListener = function (tenantId, channel) {
    // channel: a pre-authenticated Pusher/Reverb presence channel object
    // or we subscribe here if the global Echo object is available

    function showPanicOverlay(message) {
        // Remove any existing overlay first
        const existing = document.getElementById('panic-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'panic-overlay';
        overlay.style.cssText = `
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(15, 15, 25, 0.97);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 1.5rem; font-family: system-ui, sans-serif;
        `;
        overlay.innerHTML = `
            <div style="font-size: 4rem;">⚠️</div>
            <h1 style="color: #f59e0b; font-size: 1.8rem; text-align:center; margin:0; max-width:600px;">
                Ujian Sementara Dijeda
            </h1>
            <p style="color: #94a3b8; font-size: 1.1rem; text-align:center; max-width:500px; margin:0; line-height:1.6;">
                ${message || 'Admin sedang menangani kondisi darurat. Harap tetap tenang dan tunggu instruksi lebih lanjut.'}
            </p>
            <div style="color: #64748b; font-size: 0.9rem; text-align:center;">
                Jangan tutup tab ini. Ujian akan dilanjutkan otomatis.
            </div>
            <div id="panic-spinner" style="
                width:40px; height:40px; border: 4px solid #334155;
                border-top-color: #f59e0b; border-radius:50%;
                animation: panic-spin 1s linear infinite;
            "></div>
        `;

        // Add spinner animation
        const style = document.createElement('style');
        style.textContent = `@keyframes panic-spin { to { transform: rotate(360deg); } }`;
        overlay.appendChild(style);

        document.body.appendChild(overlay);

        // Block all exam interactions while panicked
        document.body.style.pointerEvents = 'none';
        overlay.style.pointerEvents = 'all';
    }

    function hidePanicOverlay() {
        const overlay = document.getElementById('panic-overlay');
        if (overlay) overlay.remove();
        document.body.style.pointerEvents = '';

        // Brief success flash
        const flash = document.createElement('div');
        flash.style.cssText = `
            position: fixed; top: 1rem; right: 1rem; z-index: 9998;
            background: #10b981; color: white; padding: 1rem 1.5rem;
            border-radius: 0.5rem; font-family: system-ui, sans-serif;
            font-weight: 600; box-shadow: 0 4px 20px rgba(16,185,129,0.4);
            animation: slide-in 0.3s ease;
        `;
        flash.textContent = '✅ Ujian dilanjutkan. Anda dapat melanjutkan menjawab.';
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 5000);
    }

    // Listen via Echo (Laravel Echo + Reverb)
    if (window.Echo) {
        window.Echo.channel(`tenant.${tenantId}.broadcast`)
            .listen('.panic.activated', (data) => {
                if (data.message === '__PANIC_DEACTIVATED__') {
                    hidePanicOverlay();
                } else {
                    showPanicOverlay(data.message);
                }
            });
    }

    return { showPanicOverlay, hidePanicOverlay };
};
