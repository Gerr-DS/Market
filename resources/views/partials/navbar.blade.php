<header class="navbar">
    <div class="navbar-inner">
        <!-- Logo -->
        <a href="/" class="navbar-logo">
            <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--clr-primary); display: flex; align-items: center; justify-content: center; color: #07110e;">
                <i data-lucide="recycle" style="width: 20px; height: 20px;"></i>
            </div>
            <span>re<b>circle</b></span>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="flex items-center gap-1" style="margin-left: 12px;">
            <a href="/products" class="nav-link {{ request()->is('products') && !request()->has('type') ? 'active' : '' }}">
                <span class="text-sm font-semibold">Jelajahi</span>
            </a>
            <a href="/products?type=food" class="nav-link {{ request('type') === 'food' ? 'active' : '' }}">
                <span class="text-sm font-semibold">Makanan Surplus</span>
            </a>
            <a href="/products?type=goods" class="nav-link {{ request('type') === 'goods' ? 'active' : '' }}">
                <span class="text-sm font-semibold">Barang Preloved</span>
            </a>
        </nav>

        <!-- Search Bar -->
        <form action="/products" method="GET" class="navbar-search">
            <i data-lucide="search" class="navbar-search-icon" style="width: 16px; height: 16px;"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari makanan surplus, barang preloved...">
        </form>

        <!-- Actions -->
        <div class="navbar-actions">
            <!-- Sell Button -->
            <a href="/sell" class="btn btn-primary btn-sm">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
                <span>Jual Barang</span>
            </a>

            <!-- Guest State -->
            <div id="navGuestActions" class="flex items-center gap-2">
                <button type="button" class="btn btn-outline btn-sm" onclick="openAuthModal('login')">
                    Masuk
                </button>
                <button type="button" class="btn btn-accent btn-sm" onclick="openAuthModal('register')">
                    Daftar
                </button>
            </div>

            <!-- Logged-in State (Populated via JS) -->
            <div id="navUserActions" class="flex items-center gap-3" style="display: none;">
                <!-- Wallet Quick View -->
                <a href="/dashboard/wallet" class="btn btn-ghost btn-sm" style="background: rgba(0,200,150,0.1); border: 1px solid var(--clr-border);">
                    <i data-lucide="wallet" style="width: 15px; height: 15px; color: var(--clr-primary);"></i>
                    <span id="navUserBalance" class="text-xs font-bold text-primary">Rp0</span>
                </a>

                <!-- Chat Link -->
                <a href="/dashboard/chat" class="btn btn-ghost btn-icon" title="Pesan & Diskusi">
                    <i data-lucide="message-square" style="width: 18px; height: 18px;"></i>
                </a>

                <!-- User Dropdown Menu -->
                <div class="dropdown" id="userMenuDropdown">
                    <button type="button" class="nav-avatar" onclick="toggleUserDropdown()" title="Menu Akun">
                        <span id="navUserAvatar">U</span>
                    </button>

                    <div id="userDropdownContent" class="dropdown-menu" style="display: none; width: 220px;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid var(--clr-border);">
                            <div class="text-xs text-muted">Masuk sebagai</div>
                            <div id="navUserName" class="text-sm font-bold truncate">Pengguna</div>
                        </div>

                        <a href="/dashboard" class="dropdown-item">
                            <i data-lucide="layout-dashboard" style="width: 16px; height: 16px;"></i>
                            <span>Dashboard Akun</span>
                        </a>
                        <a href="/dashboard/products" class="dropdown-item">
                            <i data-lucide="package" style="width: 16px; height: 16px;"></i>
                            <span>Produk Saya</span>
                        </a>
                        <a href="/dashboard/sales" class="dropdown-item">
                            <i data-lucide="store" style="width: 16px; height: 16px;"></i>
                            <span>Pesanan Masuk</span>
                        </a>
                        <a href="/dashboard/orders" class="dropdown-item">
                            <i data-lucide="shopping-bag" style="width: 16px; height: 16px;"></i>
                            <span>Pesanan Saya</span>
                        </a>
                        <a href="/dashboard/wallet" class="dropdown-item">
                            <i data-lucide="credit-card" style="width: 16px; height: 16px;"></i>
                            <span>Dompet & Saldo</span>
                        </a>
                        <a href="/admin" id="navAdminLink" class="dropdown-item" style="display: none;">
                            <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
                            <span>Panel Admin</span>
                        </a>

                        <div style="border-top: 1px solid var(--clr-border);"></div>

                        <button type="button" class="dropdown-item danger w-full text-left" onclick="logout()">
                            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                            <span>Keluar Akun</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleUserDropdown() {
        const menu = document.getElementById('userDropdownContent');
        if (menu) {
            menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
        }
    }

    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('userMenuDropdown');
        const menu = document.getElementById('userDropdownContent');
        if (dropdown && menu && !dropdown.contains(e.target)) {
            menu.style.display = 'none';
        }
    });
</script>
