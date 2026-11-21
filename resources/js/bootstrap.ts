import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/*
 * Lewati halaman peringatan ngrok untuk request dari aplikasi.
 *
 * Saat aplikasi dibuka lewat domain ngrok (https://*.ngrok-free.dev), ngrok
 * menyelipkan halaman peringatan (ERR_NGROK_6024) pada setiap request dari
 * browser. Header ini membuat ngrok langsung meneruskan request asli, sehingga
 * navigasi Inertia tetap menerima JSON (bukan halaman HTML peringatan).
 *
 * Header diabaikan server pada akses lokal biasa, jadi aman di kedua mode.
 */
const NGROK_SKIP_WARNING = 'ngrok-skip-browser-warning';

window.axios.defaults.headers.common[NGROK_SKIP_WARNING] = 'true';

// Bungkus window.fetch agar seluruh fetch di aplikasi ikut membawa header.
if (typeof window !== 'undefined' && window.fetch) {
    const originalFetch = window.fetch.bind(window);

    window.fetch = (input: RequestInfo | URL, init?: RequestInit) => {
        const headers = new Headers(init?.headers ?? (input instanceof Request ? input.headers : undefined));

        if (!headers.has(NGROK_SKIP_WARNING)) {
            headers.set(NGROK_SKIP_WARNING, 'true');
        }

        return originalFetch(input as RequestInfo, { ...init, headers });
    };
}

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const { response } = error;
        if (response && response.status === 419) {
            window.location.reload();
        }
        return Promise.reject(error);
    },
);
