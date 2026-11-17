const API_BASE_URL = (() => {
    if (window.location.protocol === 'file:') {
        return 'http://127.0.0.1:8000/api';
    }
    if (window.location.port && window.location.port !== '8000') {
        const host = window.location.hostname || '127.0.0.1';
        return `http://${host}:8000/api`;
    }
    return `${window.location.origin}/api`;
})();

function getApiToken() {
    return localStorage.getItem('api_token');
}

async function apiRequest(endpoint, options = {}) {
    const token = getApiToken();

    const headers = {
        'Accept': 'application/json',
        'ngrok-skip-browser-warning': 'true',
        'bypass-tunnel-reminder': 'true',
        ...(options.headers || {})
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    if (options.body && typeof options.body !== 'string' && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }

    const response = await fetch(
        `${API_BASE_URL}${endpoint}`,
        {
            ...options,
            headers
        }
    );

    let data;

    try {
        data = await response.json();
    } catch (error) {
        data = {
            message: 'Server mengembalikan response yang tidak valid.'
        };
    }

    if (!response.ok) {
        if (response.status === 401 && token) {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            const loginPath = window.location.pathname.includes('/admin/') ? '../login.html' : 'login.html';
            if (!window.location.pathname.endsWith('login.html')) {
                window.location.href = loginPath;
            }
        }

        const err = new Error(
            data.message || `Request gagal (${response.status})`
        );
        err.status = response.status;
        err.data = data;
        throw err;
    }

    return data;
}

async function handleLogout(redirectUrl = 'login.html') {
    try {
        await apiRequest('/logout', { method: 'POST' });
    } catch (e) {
        console.warn('Logout server request error:', e);
    }
    localStorage.removeItem('api_token');
    localStorage.removeItem('user');
    window.location.href = redirectUrl;
}

/**
 * Sinkronisasi identitas pengguna/admin di Sidebar dan Top Bar
 * Mengambil data dari localStorage secara instan, lalu menyegarkan dari GET /api/profile
 */
async function syncSidebarUser() {
    const rawUser = localStorage.getItem('user');
    if (!rawUser) return;

    let user;
    try {
        user = JSON.parse(rawUser);
    } catch (e) {
        return;
    }

    function applyUserToDOM(u, emp = null) {
        if (!u) return;

        // Admin sidebar & top bar
        const dAdminName = document.getElementById('drawer-admin-name');
        const dAdminEmail = document.getElementById('drawer-admin-email');
        const adminNameEl = document.getElementById('admin-name');
        const adminEmailEl = document.getElementById('admin-email');

        if (dAdminName) dAdminName.textContent = u.name || 'Administrator';
        if (dAdminEmail) dAdminEmail.textContent = u.email || '-';
        if (adminNameEl) adminNameEl.textContent = u.name || 'Admin';
        if (adminEmailEl) adminEmailEl.textContent = u.email || '-';

        // Employee sidebar & top bar
        const dUserName = document.getElementById('drawer-user-name');
        const dUserEmail = document.getElementById('drawer-user-email');
        const topBarName = document.getElementById('top-bar-user-name');
        const greetingName = document.getElementById('greeting-user-name');

        if (dUserName) dUserName.textContent = u.name || 'Karyawan';
        if (dUserEmail) dUserEmail.textContent = u.email || '-';
        if (topBarName) topBarName.textContent = (u.name || 'Karyawan').split(' ')[0];
        if (greetingName) greetingName.textContent = (u.name || 'Karyawan').split(' ')[0];
    }

    // 1. Tampilkan data dari localStorage seketika
    applyUserToDOM(user);

    // 2. Refresh dari endpoint profil backend agar selalu tersinkronisasi dengan database
    try {
        const token = getApiToken();
        if (token) {
            const profileRes = await apiRequest('/profile');
            if (profileRes && profileRes.user) {
                localStorage.setItem('user', JSON.stringify(profileRes.user));
                applyUserToDOM(profileRes.user, profileRes.employee);
            }
        }
    } catch (err) {
        // Abaikan jika network offline, data localStorage sudah tampil
    }
}

// Inisialisasi otomatis sinkronisasi identitas pengguna saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    syncSidebarUser();
});