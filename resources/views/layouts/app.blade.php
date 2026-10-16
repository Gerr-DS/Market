<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ReCircle — Solusi Cerdas Barang Preloved & Makanan Surplus')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Leaflet Maps CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

    <!-- Main Design System CSS -->
    <link rel="stylesheet" href="/css/style.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- QR Code Generator -->
    <script src="/js/qrcode.js"></script>

    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    @include('partials.navbar')

    <!-- Toast Notifications Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- Global Auth Modal -->
    <div id="globalAuthModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h3 id="authModalTitle" class="modal-title">Masuk ke ReCircle</h3>
                    <p id="authModalSub" class="text-sm text-muted mt-1">Dukung ekonomi sirkular & selamatkan barang bermakna.</p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeAuthModal()">✕</button>
            </div>

            <form id="globalAuthForm" class="flex flex-col gap-4">
                <div id="authNameGroup" class="form-group" style="display: none;">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" id="authName" name="name" class="form-input" placeholder="Nama Anda">
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Email</label>
                    <input type="email" id="authEmail" name="email" class="form-input" required placeholder="nama@email.com">
                </div>

                <div id="authPhoneGroup" class="form-group" style="display: none;">
                    <label class="form-label">Nomor Telepon (WhatsApp)</label>
                    <input type="tel" id="authPhone" name="phone" class="form-input" placeholder="08xxxxxxxxxx">
                </div>

                <div class="form-group">
                    <label class="form-label">Kata Sandi</label>
                    <input type="password" id="authPassword" name="password" class="form-input" required minlength="8" placeholder="Minimal 8 karakter">
                </div>

                <div id="authErrorNotice" class="text-xs text-danger" style="display: none;"></div>

                <button type="submit" id="authSubmitBtn" class="btn btn-primary w-full mt-2">
                    Masuk Sekarang
                </button>

                <div class="text-center mt-3">
                    <button type="button" id="authSwitchModeBtn" class="btn-ghost text-sm text-primary" onclick="toggleAuthMode()">
                        Belum punya akun? Daftar gratis di sini
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Core Global Helper Scripts -->
    <script>
        const API = '/api/v1';
        let isRegisterMode = false;

        function token() {
            return localStorage.getItem('surplus_token');
        }

        function setToken(tok) {
            if (tok) {
                localStorage.setItem('surplus_token', tok);
            } else {
                localStorage.removeItem('surplus_token');
            }
            updateGlobalNavAuth();
        }

        function rupiah(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(value || 0);
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const iconMap = {
                success: 'check-circle',
                error: 'alert-circle',
                info: 'info'
            };
            const icon = iconMap[type] || 'info';

            toast.innerHTML = `
                <i data-lucide="${icon}" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <div style="flex: 1;">${escapeHtml(message)}</div>
            `;
            container.appendChild(toast);
            lucide.createIcons();

            setTimeout(() => {
                toast.remove();
            }, 3200);
        }

        function escapeHtml(s = '') {
            return String(s).replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[c]));
        }

        function imageUrl(path) {
            if (!path) return 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=700&q=80';
            if (path.startsWith('http')) return path;
            return '/storage/' + path.replace(/^\//, '');
        }

        function openAuthModal(mode = 'login') {
            isRegisterMode = (mode === 'register');
            const modal = document.getElementById('globalAuthModal');
            const nameGrp = document.getElementById('authNameGroup');
            const phoneGrp = document.getElementById('authPhoneGroup');
            const title = document.getElementById('authModalTitle');
            const sub = document.getElementById('authModalSub');
            const submitBtn = document.getElementById('authSubmitBtn');
            const switchBtn = document.getElementById('authSwitchModeBtn');
            const err = document.getElementById('authErrorNotice');

            err.style.display = 'none';

            if (isRegisterMode) {
                nameGrp.style.display = 'flex';
                phoneGrp.style.display = 'flex';
                title.textContent = 'Daftar Akun Baru';
                sub.textContent = 'Bergabunglah bersama ribuan penggerak ekonomi sirkular.';
                submitBtn.textContent = 'Buat Akun ReCircle';
                switchBtn.textContent = 'Sudah punya akun? Masuk di sini';
            } else {
                nameGrp.style.display = 'none';
                phoneGrp.style.display = 'none';
                title.textContent = 'Masuk ke ReCircle';
                sub.textContent = 'Selamat datang kembali! Lanjutkan transaksi & aksimu.';
                submitBtn.textContent = 'Masuk Sekarang';
                switchBtn.textContent = 'Belum punya akun? Daftar gratis di sini';
            }

            modal.style.display = 'flex';
            lucide.createIcons();
        }

        function closeAuthModal() {
            document.getElementById('globalAuthModal').style.display = 'none';
        }

        function toggleAuthMode() {
            openAuthModal(isRegisterMode ? 'login' : 'register');
        }

        document.getElementById('globalAuthForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('authSubmitBtn');
            const err = document.getElementById('authErrorNotice');
            err.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses...';

            const payload = {
                email: document.getElementById('authEmail').value.trim(),
                password: document.getElementById('authPassword').value
            };

            if (isRegisterMode) {
                payload.name = document.getElementById('authName').value.trim();
                payload.phone = document.getElementById('authPhone').value.trim();
            }

            try {
                const endpoint = isRegisterMode ? `${API}/auth/register` : `${API}/auth/login`;
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Otentikasi gagal.');
                }

                setToken(data.token);
                closeAuthModal();
                showToast(isRegisterMode ? 'Pendaftaran berhasil! Selamat datang.' : 'Berhasil masuk ke akun.', 'success');
                
                // If on auth page, redirect to dashboard
                if (window.location.pathname === '/login' || window.location.pathname === '/register') {
                    window.location.href = '/dashboard';
                } else {
                    window.location.reload();
                }
            } catch (error) {
                err.textContent = error.message;
                err.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = isRegisterMode ? 'Buat Akun ReCircle' : 'Masuk Sekarang';
            }
        });

        async function updateGlobalNavAuth() {
            const guestNav = document.getElementById('navGuestActions');
            const userNav = document.getElementById('navUserActions');
            const userName = document.getElementById('navUserName');
            const userAvatar = document.getElementById('navUserAvatar');
            const userBalance = document.getElementById('navUserBalance');
            const adminLink = document.getElementById('navAdminLink');

            if (!guestNav || !userNav) return;

            const t = token();
            if (!t) {
                guestNav.style.display = 'flex';
                userNav.style.display = 'none';
                if (adminLink) adminLink.style.display = 'none';
                return;
            }

            try {
                const res = await fetch(`${API}/user/profile`, {
                    headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${t}` }
                });
                if (!res.ok) {
                    if (res.status === 401) {
                        setToken(null);
                        guestNav.style.display = 'flex';
                        userNav.style.display = 'none';
                    }
                    return;
                }
                const user = await res.json();
                guestNav.style.display = 'none';
                userNav.style.display = 'flex';
                if (adminLink) adminLink.style.display = user.role === 'admin' ? 'flex' : 'none';

                if (userName) userName.textContent = user.name || 'Pengguna';
                if (userAvatar) userAvatar.textContent = (user.name || 'U').charAt(0).toUpperCase();
                if (userBalance) userBalance.textContent = rupiah(user.wallet_balance);
            } catch (e) {
                console.error('Profile fetch error', e);
            }
        }

        function logout() {
            localStorage.removeItem('surplus_token');
            showToast('Anda telah keluar dari akun.', 'info');
            setTimeout(() => {
                window.location.href = '/';
            }, 500);
        }

        // Initialize icons and auth state on load
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            updateGlobalNavAuth();
        });
    </script>

    @stack('scripts')
</body>
</html>
