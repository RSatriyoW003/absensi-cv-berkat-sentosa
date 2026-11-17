let selectedRole = 'employee';

// DOM Elements
const loginFormContainer = document.getElementById('login-form-container');
const adminRegisterContainer = document.getElementById('admin-register-container');
const adminRegisterSwitch = document.getElementById('admin-register-switch');

const loginForm = document.getElementById('login-form');
const loginButton = document.getElementById('login-button');
const loginBtnIcon = document.getElementById('login-btn-icon');
const loginBtnText = document.getElementById('login-btn-text');
const loginMessage = document.getElementById('login-message');
const roleSubtitleText = document.getElementById('role-subtitle-text');
const emailLabel = document.getElementById('email-label');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const rememberMeCheckbox = document.getElementById('remember-me');
const loginCardBox = document.getElementById('login-card-box');
const capsIndicator = document.getElementById('caps-indicator');

const btnTabEmployee = document.getElementById('btn-tab-employee');
const btnTabAdmin = document.getElementById('btn-tab-admin');

const adminRegisterForm = document.getElementById('admin-register-form');
const regButton = document.getElementById('reg-button');
const regMessage = document.getElementById('reg-message');

// ========================================================
// INISIALISASI HALAMAN & REMEMBER ME
// ========================================================
document.addEventListener('DOMContentLoaded', () => {
    // Muat email tersimpan jika ada
    const rememberedEmail = localStorage.getItem('cbs_remember_email');
    if (rememberedEmail && emailInput) {
        emailInput.value = rememberedEmail;
        if (rememberMeCheckbox) rememberMeCheckbox.checked = true;
    }

    // Pasang listener Caps Lock
    setupCapsLockDetection('password', 'caps-indicator');

    // Default ke Employee
    switchLoginRole('employee');
});

// ========================================================
// SWITCH ROLE (KARYAWAN VS ADMINISTRATOR)
// ========================================================
function switchLoginRole(role) {
    selectedRole = role;

    // Reset container view
    if (loginFormContainer) loginFormContainer.style.display = 'block';
    if (adminRegisterContainer) adminRegisterContainer.style.display = 'none';
    if (loginMessage) {
        loginMessage.style.display = 'none';
        loginMessage.textContent = '';
    }

    if (role === 'admin') {
        if (btnTabAdmin) {
            btnTabAdmin.classList.add('active', 'active-admin');
        }
        if (btnTabEmployee) {
            btnTabEmployee.classList.remove('active', 'active-admin');
        }

        if (roleSubtitleText) {
            roleSubtitleText.textContent = 'Panel Kontrol Manajemen & Verifikasi Presensi';
        }
        if (emailLabel) {
            emailLabel.textContent = 'Email Akun Administrator';
        }
        if (emailInput && !emailInput.value) {
            emailInput.placeholder = 'admin@cbs.com';
        }
        if (loginButton) {
            loginButton.classList.add('btn-admin-submit');
        }
        if (loginBtnIcon) loginBtnIcon.textContent = '🛡️';
        if (loginBtnText) loginBtnText.textContent = 'Masuk sebagai Administrator';
        if (adminRegisterSwitch) adminRegisterSwitch.style.display = 'block';
    } else {
        selectedRole = 'employee';
        if (btnTabEmployee) {
            btnTabEmployee.classList.add('active');
            btnTabEmployee.classList.remove('active-admin');
        }
        if (btnTabAdmin) {
            btnTabAdmin.classList.remove('active', 'active-admin');
        }

        if (roleSubtitleText) {
            roleSubtitleText.textContent = 'Portal Presensi Terintegrasi & Validasi Lokasi CV Berkat Sentosa';
        }
        if (emailLabel) {
            emailLabel.textContent = 'Email Akun Karyawan';
        }
        if (emailInput && !emailInput.value) {
            emailInput.placeholder = 'budi@cbs.com';
        }
        if (loginButton) {
            loginButton.classList.remove('btn-admin-submit');
        }
        if (loginBtnIcon) loginBtnIcon.textContent = '🚀';
        if (loginBtnText) loginBtnText.textContent = 'Masuk sebagai Karyawan';
        if (adminRegisterSwitch) adminRegisterSwitch.style.display = 'none';
    }
}

// Kompatibilitas dengan pemanggil lama
function showLoginForm(role) {
    switchLoginRole(role || 'employee');
}

// ========================================================
// AKSES CEPAT DEMO AUTO-FILL
// ========================================================
function fillDemoCredentials(email, password, role) {
    switchLoginRole(role);
    if (emailInput) {
        emailInput.value = email;
        flashHighlight(emailInput);
    }
    if (passwordInput) {
        passwordInput.value = password;
        flashHighlight(passwordInput);
    }
}

function flashHighlight(element) {
    element.style.transition = 'all 0.2s ease';
    element.style.borderColor = '#2563eb';
    element.style.backgroundColor = '#eff6ff';
    setTimeout(() => {
        element.style.borderColor = '';
        element.style.backgroundColor = '';
    }, 600);
}

// ========================================================
// TOGGLE PASSWORD VISIBILITY (EYE ICON)
// ========================================================
function togglePasswordVisibility(inputId, btnElement) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        btnElement.textContent = '🙈';
        btnElement.title = 'Sembunyikan Password';
    } else {
        input.type = 'password';
        btnElement.textContent = '👁️';
        btnElement.title = 'Tampilkan Password';
    }
}

// ========================================================
// DETEKSI CAPS LOCK REAL-TIME
// ========================================================
function setupCapsLockDetection(inputId, indicatorId) {
    const input = document.getElementById(inputId);
    const indicator = document.getElementById(indicatorId);
    if (!input || !indicator) return;

    const checkCaps = (e) => {
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            indicator.style.display = 'inline-flex';
        } else {
            indicator.style.display = 'none';
        }
    };

    input.addEventListener('keyup', checkCaps);
    input.addEventListener('keydown', checkCaps);
    input.addEventListener('blur', () => {
        indicator.style.display = 'none';
    });
}

// ========================================================
// BANTUAN AKUN MODAL / ALERT
// ========================================================
function handleLoginHelp() {
    alert(
        "💡 Bantuan Akun CV Berkat Sentosa:\n\n" +
        "1. Karyawan:\n" +
        "   - Masukkan email dan password karyawan yang telah didaftarkan HRD/Admin.\n\n" +
        "2. Administrator:\n" +
        "   - Pilih tab 'Administrator' lalu masukkan email dan password admin.\n\n" +
        "Jika mengalami kendala lupa password, silakan hubungi bagian IT/Administrator perusahaan."
    );
}

// ========================================================
// FORM DAFTAR ADMIN BARU
// ========================================================
function showAdminRegisterForm() {
    if (loginFormContainer) loginFormContainer.style.display = 'none';
    if (adminRegisterContainer) adminRegisterContainer.style.display = 'block';
    if (regMessage) {
        regMessage.style.display = 'none';
        regMessage.textContent = '';
    }
    if (adminRegisterForm) adminRegisterForm.reset();
}

function checkPasswordStrength(val) {
    const strengthContainer = document.getElementById('strength-container');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    if (!strengthContainer || !strengthBar || !strengthText) return;

    if (!val || val.length === 0) {
        strengthContainer.style.display = 'none';
        return;
    }

    strengthContainer.style.display = 'block';
    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 8) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[A-Z]/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;

    if (score <= 1) {
        strengthBar.style.width = '25%';
        strengthBar.style.background = '#dc2626';
        strengthText.style.color = '#dc2626';
        strengthText.textContent = 'Kekuatan: Lemah (minimal 6 karakter)';
    } else if (score === 2) {
        strengthBar.style.width = '50%';
        strengthBar.style.background = '#ea580c';
        strengthText.style.color = '#ea580c';
        strengthText.textContent = 'Kekuatan: Sedang (tambahkan angka/huruf besar)';
    } else if (score === 3) {
        strengthBar.style.width = '75%';
        strengthBar.style.background = '#ca8a04';
        strengthText.style.color = '#ca8a04';
        strengthText.textContent = 'Kekuatan: Bagus';
    } else {
        strengthBar.style.width = '100%';
        strengthBar.style.background = '#16a34a';
        strengthText.style.color = '#16a34a';
        strengthText.textContent = 'Kekuatan: Sangat Kuat & Aman';
    }
}

// ========================================================
// PROSES LOGIN
// ========================================================
if (loginForm) {
    loginForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const email = emailInput ? emailInput.value.trim() : '';
        const password = passwordInput ? passwordInput.value : '';

        // Tampilan loading interaktif
        loginButton.disabled = true;
        const originalBtnHtml = loginButton.innerHTML;
        loginButton.innerHTML = '<span>⏳ Memeriksa kredensial...</span>';
        loginMessage.style.display = 'none';
        loginMessage.textContent = '';

        try {
            const data = await apiRequest('/login', {
                method: 'POST',
                body: { email, password }
            });

            // Sinkronkan role otomatis jika kredensial milik role lain
            if (data.user && data.user.role && data.user.role !== selectedRole) {
                switchLoginRole(data.user.role);
            }

            // Simpan Remember Me
            if (rememberMeCheckbox && rememberMeCheckbox.checked) {
                localStorage.setItem('cbs_remember_email', email);
            } else {
                localStorage.removeItem('cbs_remember_email');
            }

            localStorage.setItem('api_token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));

            loginButton.style.background = '#16a34a';
            loginButton.innerHTML = '<span>✅ Login Berhasil! Mengalihkan...</span>';

            setTimeout(() => {
                if (data.user && data.user.role === 'admin') {
                    window.location.href = 'admin/dashboard.html';
                } else {
                    window.location.href = 'dashboard.html';
                }
            }, 600);

        } catch (error) {
            console.error('Login gagal:', error);

            // Animasi getar kartu (shake)
            if (loginCardBox) {
                loginCardBox.classList.remove('shake');
                void loginCardBox.offsetWidth; // trigger reflow
                loginCardBox.classList.add('shake');
                setTimeout(() => loginCardBox.classList.remove('shake'), 500);
            }

            loginMessage.style.display = 'block';
            loginMessage.style.background = '#fee2e2';
            loginMessage.style.border = '1px solid #fecaca';
            loginMessage.style.color = '#dc2626';
            loginMessage.textContent = '⚠️ ' + (error.message || 'Gagal login. Periksa email dan password Anda.');

            loginButton.disabled = false;
            loginButton.innerHTML = originalBtnHtml;
        }
    });
}

// ========================================================
// PROSES DAFTAR ADMIN BARU
// ========================================================
if (adminRegisterForm) {
    adminRegisterForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const name = document.getElementById('reg-name').value;
        const email = document.getElementById('reg-email').value;
        const password = document.getElementById('reg-password').value;

        regButton.disabled = true;
        const originalRegHtml = regButton.innerHTML;
        regButton.innerHTML = '<span>⏳ Mendaftarkan admin...</span>';
        regMessage.style.display = 'none';
        regMessage.textContent = '';

        try {
            const data = await apiRequest('/register-admin', {
                method: 'POST',
                body: { name, email, password }
            });

            regMessage.style.display = 'block';
            regMessage.style.background = '#dcfce7';
            regMessage.style.border = '1px solid #bbf7d0';
            regMessage.style.color = '#16a34a';
            regMessage.textContent = '🎉 Pendaftaran berhasil! Mengalihkan ke dashboard admin...';

            localStorage.setItem('api_token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));

            setTimeout(() => {
                window.location.href = 'admin/dashboard.html';
            }, 1000);

        } catch (error) {
            console.error('Registrasi admin gagal:', error);
            regMessage.style.display = 'block';
            regMessage.style.background = '#fee2e2';
            regMessage.style.border = '1px solid #fecaca';
            regMessage.style.color = '#dc2626';
            regMessage.textContent = '⚠️ ' + (error.message || 'Registrasi gagal.');

            regButton.disabled = false;
            regButton.innerHTML = originalRegHtml;
        }
    });
}