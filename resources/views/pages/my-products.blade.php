@extends('layouts.app')

@section('title', 'Produk Saya — ReCircle')

@section('content')
<div class="page">
    <div class="container">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold">Produk Saya</h1>
                <p class="text-sm text-muted">Hanya produk yang sudah disetujui dan ditayangkan yang muncul di sini.</p>
            </div>
            <a href="/sell" class="btn btn-primary btn-sm">
                <i data-lucide="plus-circle" style="width: 15px; height: 15px;"></i>
                <span>+ Tambah Produk Baru</span>
            </a>
        </div>

        <!-- Navigation Tabs for Dashboard -->
        <div class="tabs">
            <a href="/dashboard" class="tab">Ringkasan</a>
            <a href="/dashboard/products" class="tab active">Produk Saya</a>
            <a href="/dashboard/sales" class="tab">Pesanan Masuk (Penjual)</a>
            <a href="/dashboard/orders" class="tab">Pesanan Saya (Pembeli)</a>
            <a href="/dashboard/wallet" class="tab">Dompet & Saldo</a>
            <a href="/dashboard/chat" class="tab">Pesan Chat</a>
        </div>

        <!-- Product List Container -->
        <div id="myProductsContainer" class="flex flex-col gap-4">
            <div class="card p-8 flex justify-center items-center">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let allMyProducts = [];

    async function loadMyProducts() {
        if (!token()) {
            openAuthModal('login');
            return;
        }

        const container = document.getElementById('myProductsContainer');
        try {
            const res = await fetch(`${API}/user/products`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });

            if (!res.ok) throw new Error('Gagal memuat produk saya.');
            const json = await res.json();
            allMyProducts = (json.data || []).filter(p => p.status === 'active');

            renderMyProducts();
        } catch (e) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Gagal Memuat Produk</h3>
                    <p>${escapeHtml(e.message)}</p>
                </div>
            `;
        }
    }

    function renderMyProducts() {
        const container = document.getElementById('myProductsContainer');
        const items = allMyProducts;

        if (items.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h3>Tidak Ada Produk</h3>
                    <p>Belum ada produk pada kategori status ini.</p>
                    <a href="/sell" class="btn btn-primary btn-sm mt-4">+ Jual Produk Baru</a>
                </div>
            `;
            return;
        }

        container.innerHTML = items.map(p => {
            const img = p.images?.[0]?.image_path;
            const photoSrc = imageUrl(img);
            const isFood = p.type === 'food';

            return `
                <div class="card p-5 flex items-center justify-between flex-wrap gap-4" style="background: var(--clr-card);">
                    <div class="flex items-center gap-4">
                        <img src="${photoSrc}" class="upload-thumb" style="width: 72px; height: 72px; border-radius: var(--radius-md);">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge ${isFood ? 'badge-warning' : 'badge-primary'}">${isFood ? 'Makanan Surplus' : 'Preloved'}</span>
                                <span class="badge badge-success">Ditayangkan</span>
                            </div>
                            <h3 class="font-bold text-base mb-1">${escapeHtml(p.title)}</h3>
                            <div class="text-primary font-bold text-base">${rupiah(p.system_price)}</div>
                            <div class="text-xs text-muted">Stok: ${Number(p.stock ?? 0)}${Number(p.stock ?? 0) === 0 ? ' · tidak tampil di katalog' : ''}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="/products/${p.id}" class="btn btn-outline btn-sm">Lihat Halaman</a>
                    </div>
                </div>
            `;
        }).join('');

        lucide.createIcons();
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadMyProducts();
    });
</script>
@endpush
