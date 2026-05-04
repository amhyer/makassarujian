import './bootstrap';
import { createApp } from 'vue';
import QuestionForm from './components/QuestionForm.vue';
import QuestionList from './components/QuestionList.vue';
import QuestionStats from './components/QuestionStats.vue';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const vueRoot = document.querySelector('#app');
if (vueRoot) {
    const app = createApp({});
    app.component('question-form', QuestionForm);
    app.component('question-list', QuestionList);
    app.component('question-stats', QuestionStats);
    app.mount('#app');
}

let pendingRequests = 0;

const emitLoading = (state, message = null) => {
    window.dispatchEvent(new CustomEvent('app:loading', {
        detail: { state, message },
    }));
};

const showLoading = (message = 'Memproses data...') => {
    emitLoading(true, message);
};

const hideLoadingIfIdle = () => {
    if (pendingRequests <= 0) {
        emitLoading(false);
    }
};

window.addEventListener('beforeunload', () => {
    showLoading('Memuat halaman...');
});

document.addEventListener('submit', (event) => {
    if (!event.defaultPrevented) {
        showLoading('Menyimpan data...');

        const submitter = event.submitter;
        if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
            submitter.dataset.originalLabel = submitter.tagName === 'INPUT'
                ? (submitter.value || 'Submit')
                : (submitter.innerHTML || 'Submit');

            submitter.disabled = true;
            submitter.classList.add('is-loading');

            if (submitter.tagName === 'INPUT') {
                submitter.value = 'Memproses...';
            } else {
                submitter.innerHTML = '<span class="btn-loading-spinner" aria-hidden="true"></span><span>Memproses...</span>';
            }
        }
    }
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link) {
        return;
    }

    const href = link.getAttribute('href') || '';
    const target = link.getAttribute('target');
    const isModifiedClick = event.ctrlKey || event.metaKey || event.shiftKey || event.altKey;
    const isHash = href.startsWith('#');
    const isJsLink = href.toLowerCase().startsWith('javascript:');
    const isDownload = link.hasAttribute('download');

    if (
        isModifiedClick ||
        target === '_blank' ||
        isHash ||
        isJsLink ||
        isDownload ||
        event.defaultPrevented
    ) {
        return;
    }

    try {
        const url = new URL(link.href, window.location.origin);
        if (url.origin === window.location.origin) {
            showLoading('Memuat halaman...');
        }
    } catch (_e) {
        // Ignore malformed URLs.
    }
});

if (typeof window.fetch === 'function') {
    const originalFetch = window.fetch.bind(window);
    window.fetch = async (...args) => {
        pendingRequests++;
        showLoading('Mengambil data...');

        try {
            return await originalFetch(...args);
        } finally {
            pendingRequests--;
            hideLoadingIfIdle();
        }
    };
}

if (window.axios?.interceptors) {
    window.axios.interceptors.request.use((config) => {
        pendingRequests++;
        showLoading('Mengambil data...');
        return config;
    });

    window.axios.interceptors.response.use(
        (response) => {
            pendingRequests--;
            hideLoadingIfIdle();
            return response;
        },
        (error) => {
            pendingRequests--;
            hideLoadingIfIdle();
            return Promise.reject(error);
        }
    );
}
