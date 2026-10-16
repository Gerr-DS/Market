@extends('layouts.app')

@section('title', 'Dashboard Akun — ReCircle')

@section('content')
<div class="page">
    <div class="container">
        <!-- Welcome Hero -->
        <div class="card p-8 mb-8" style="background: linear-gradient(135deg, rgba(0,200,150,0.12), rgba(13,31,25,0.8)); border-color: rgba(0,200,150,0.25);">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="badge badge-primary mb-2">Panel Anggota Komunitas</div>
                    <h1 class="text-2xl font-bold">Halo, <span id="dashHeroName" class="text-primary">Teman</span> 👋</h1>
                    <p class="text-sm text-muted mt-1" id="dashHeroEmail">Memuat akun...</p>
                </div>
                <div class="flex gap-3">
                    <a href="/sell" class="btn btn-primary btn-sm">
                        <i data-lucide="plus-circle" style="width: 15px; height: 15px;"></i>
                        <span>+ Jual Barang Baru</span>
                    </a>
                    <button type="button" class="btn btn-outline btn-sm text-danger" onclick="logout()">
                        <i data-lucide="log-out" style="width: 15px; height: 15px;"></i>
                        <span>Keluar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs for Dashboard -->
        <div class="tabs">
            <a href="/dashboard" class="tab active">Ringkasan</a>
            <a href="/dashboard/products" class="tab">Produk Saya</a>
            <a href="/dashboard/sales" class="tab">Pesanan Masuk (Penjual)</a>
            <a href="/dashboard/orders" class="tab">Pesanan Saya (Pembeli)</a>
            <a href="/dashboard/wallet" class="tab">Dompet & Saldo</a>
            <a href="/dashboard/chat" class="tab">Pesan Chat</a>
        </div>

        <!-- Dashboard Metrics Grid -->
        <div class="stats-grid mb-8">
            <!-- Wallet Card -->
            <div class="stat-card" style="border-color: var(--clr-primary);">
                <div class="stat-label">Saldo Dompet ReCircle</div>
                <div class="stat-value text-primary" id="dashWalletBalance">Rp0</div>
                <div class="flex gap-2 mt-3">
                    <a href="/dashboard/wallet" class="btn btn-primary btn-sm" style="flex: 1;">Isi Saldo QR</a>
                    <a href="/dashboard/wallet" class="btn btn-outline btn-sm" style="flex: 1;">Tarik Saldo</a>
                </div>
            </div>

            <!-- Active Products -->
            <div class="stat-card">
                <div class="stat-label">Produk Yang Ditayangkan</div>
                <div class="stat-value" id="dashActiveProducts">0</div>
                <a href="/dashboard/products" class="text-xs text-primary font-semibold mt-3 block">Kelola Produk →</a>
            </div>

            <!-- Incoming Sales -->
            <div class="stat-card">
                <div class="stat-label">Pesanan Masuk (Jual)</div>
                <div class="stat-value text-accent" id="dashIncomingOrders">0</div>
                <a href="/dashboard/sales" class="text-xs text-primary font-semibold mt-3 block">Proses Pesanan →</a>
            </div>

            <!-- Purchases -->
            <div class="stat-card">
                <div class="stat-label">Pesanan Saya (Beli)</div>
                <div class="stat-value text-info" id="dashPurchases">0</div>
                <a href="/dashboard/orders" class="text-xs text-primary font-semibold mt-3 block">Pantau Status →</a>
            </div>
        </div>

        <!-- Recent Activity Sections -->
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <!-- Recent Sales -->
            <div class="card p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider mb-4 flex items-center justify-between">
                    <span>Pesanan Masuk Terbaru</span>
                    <a href="/dashboard/sales" class="text-xs text-primary">Lihat Semua</a>
                </h3>
                <div id="dashRecentSalesList" class="flex flex-col gap-3">
                    <div class="text-xs text-muted text-center py-4">Memuat transaksi...</div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider mb-4 flex items-center justify-between">
                    <span>Belanjaan Terakhirmu</span>
                    <a href="/dashboard/orders" class="text-xs text-primary">Lihat Semua</a>
                </h3>
                <div id="dashRecentOrdersList" class="flex flex-col gap-3">
                    <div class="text-xs text-muted text-center py-4">Memuat transaksi...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadDashboardOverview() {
        if (!token()) {
            openAuthModal('login');
            return;
        }

        const headers = {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token()}`
        };

        try {
            const [profileRes, productsRes, salesRes, ordersRes] = await Promise.all([
                fetch(`${API}/user/profile`, { headers }),
                fetch(`${API}/user/products`, { headers }),
                fetch(`${API}/user/sales`, { headers }),
                fetch(`${API}/user/orders`, { headers })
            ]);

            if ([profileRes, productsRes, salesRes, ordersRes].some(r => r.status === 401)) {
                logout();
                return;
            }

            const profile = await profileRes.json();
            const products = await productsRes.json();
            const sales = await salesRes.json();
            const orders = await ordersRes.json();

            document.getElementById('dashHeroName').textContent = profile.name || 'Sahabat Sirkular';
            document.getElementById('dashHeroEmail').textContent = profile.email || '';
            document.getElementById('dashWalletBalance').textContent = rupiah(profile.wallet_balance);

            const activeItems = (products.data || []).filter(p => p.status === 'active');
            document.getElementById('dashActiveProducts').textContent = activeItems.length;

            const incomingList = sales.data || [];
            document.getElementById('dashIncomingOrders').textContent = incomingList.length;

            const myOrdersList = orders.data || [];
            document.getElementById('dashPurchases').textContent = myOrdersList.length;

            // Render Recent Sales preview
            const salesBox = document.getElementById('dashRecentSalesList');
            salesBox.innerHTML = incomingList.slice(0, 3).map(s => `
                <div class="card p-3 flex items-center justify-between" style="background: var(--clr-surface);">
                    <div>
                        <div class="font-semibold text-xs truncate" style="max-width: 200px;">${escapeHtml(s.product?.title || 'Produk')}</div>
                        <span class="text-xs text-muted">Pembeli: ${escapeHtml(s.buyer?.name || '—')}</span>
                    </div>
                    <strong class="text-xs text-primary">${rupiah(s.total_amount)}</strong>
                </div>
            `).join('') || '<div class="text-xs text-muted text-center py-4">Belum ada pesanan masuk.</div>';

            // Render Recent Orders preview
            const ordersBox = document.getElementById('dashRecentOrdersList');
            ordersBox.innerHTML = myOrdersList.slice(0, 3).map(o => `
                <div class="card p-3 flex items-center justify-between" style="background: var(--clr-surface);">
                    <div>
                        <div class="font-semibold text-xs truncate" style="max-width: 200px;">${escapeHtml(o.product?.title || 'Produk')}</div>
                        <span class="text-xs text-muted">${escapeHtml(o.order_number)}</span>
                    </div>
                    <strong class="text-xs text-primary">${rupiah(o.total_amount)}</strong>
                </div>
            `).join('') || '<div class="text-xs text-muted text-center py-4">Belum ada pesanan belanja.</div>';

            lucide.createIcons();
        } catch (e) {
            console.error('Dashboard load error', e);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadDashboardOverview();
    });
</script>
@endpush
