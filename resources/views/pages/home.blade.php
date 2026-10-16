@extends('layouts.app')

@section('title', 'ReCircle — Pasar Barang Preloved & Makanan Surplus Berkelanjutan')

@section('content')
<div class="page" style="padding-top: var(--nav-height);">
    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <div style="max-width: 680px;">
                <div class="hero-tag">
                    <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i>
                    <span>Pasar Sirkular Pertama dengan AI Verifikasi</span>
                </div>
                
                <h1>
                    Beri Barang & Makanan<br>
                    <span class="highlight">Kesempatan Kedua.</span>
                </h1>
                
                <p class="hero-desc">
                    Temukan makanan surplus segar dan barang preloved pilihan dari komunitas sekitarmu. Lebih hemat untuk kantongmu, lebih baik untuk masa depan bumi.
                </p>

                <!-- Search form -->
                <form action="/products" method="GET" class="hero-search mb-6">
                    <input type="text" name="q" placeholder="Cari donat, roti, pakaian preloved, blender..." aria-label="Cari barang">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                        <span>Cari Barang</span>
                    </button>
                </form>

                <!-- Impact Metrics -->
                <div class="flex items-center gap-6 flex-wrap text-sm text-muted">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-check" style="width: 16px; height: 16px; color: var(--clr-primary);"></i>
                        <span>Verifikasi Kualitas AI</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="truck" style="width: 16px; height: 16px; color: var(--clr-primary);"></i>
                        <span>Kurir & Ambil Langsung (COD)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="wallet" style="width: 16px; height: 16px; color: var(--clr-primary);"></i>
                        <span>Saldo Escrow Aman</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ambient Glow Orbs -->
        <div class="hero-bg-orb" style="width: 400px; height: 400px; background: var(--clr-primary); top: -100px; right: -50px;"></div>
        <div class="hero-bg-orb" style="width: 300px; height: 300px; background: #00e8b0; bottom: -50px; left: 10%;"></div>
    </section>

    <!-- Impact Stats Bar -->
    <section class="container mb-8">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Makanan Terselamatkan</div>
                <div class="stat-value text-primary">1.840+ <span class="text-sm">kg</span></div>
                <div class="stat-sub">Dari restoran & supermarket mitra</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Barang Preloved Terjual</div>
                <div class="stat-value text-accent">920+ <span class="text-sm">unit</span></div>
                <div class="stat-sub">Memperpanjang usia pakai produk</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Penghematan Pengguna</div>
                <div class="stat-value text-success">Rp56+ <span class="text-sm">Juta</span></div>
                <div class="stat-sub">Rata-rata diskon hingga 65%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Emisi CO2 Dicegah</div>
                <div class="stat-value text-info">2.450 <span class="text-sm">kg CO2e</span></div>
                <div class="stat-sub">Kontribusi nyata aksi lingkungan</div>
            </div>
        </div>
    </section>

    <!-- Main Marketplace Section -->
    <section class="container mt-8" id="katalog">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div>
                <h2 class="section-title">Temuan Terbaik Minggu Ini</h2>
                <p class="text-sm text-muted">Produk terverifikasi yang siap kamu selamatkan hari ini.</p>
            </div>

            <!-- Type Filter Chips -->
            <div class="category-list">
                <button type="button" class="category-chip active" data-filter="all" onclick="filterType('all', this)">
                    <i data-lucide="grid" style="width: 14px; height: 14px;"></i>
                    <span>Semua Kategori</span>
                </button>
                <button type="button" class="category-chip" data-filter="food" onclick="filterType('food', this)">
                    <i data-lucide="utensils" style="width: 14px; height: 14px;"></i>
                    <span>Makanan Surplus</span>
                </button>
                <button type="button" class="category-chip" data-filter="goods" onclick="filterType('goods', this)">
                    <i data-lucide="package" style="width: 14px; height: 14px;"></i>
                    <span>Barang Preloved</span>
                </button>
            </div>
        </div>

        <!-- Products Grid Container -->
        <div id="homeProductGrid" class="product-grid">
            <!-- Loading Skeletons -->
            <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
                <div style="background: var(--clr-surface); height: 24px; width: 40%; border-radius: 4px;"></div>
            </div>
            <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
                <div style="background: var(--clr-surface); height: 24px; width: 40%; border-radius: 4px;"></div>
            </div>
            <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
                <div style="background: var(--clr-surface); height: 24px; width: 40%; border-radius: 4px;"></div>
            </div>
            <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
                <div style="background: var(--clr-surface); height: 24px; width: 40%; border-radius: 4px;"></div>
            </div>
        </div>

        <!-- View All Products CTA -->
        <div class="text-center mt-8">
            <a href="/products" class="btn btn-outline btn-lg">
                <span>Lihat Seluruh Katalog Produk</span>
                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
            </a>
        </div>
    </section>

    <!-- Eco Banner Call To Action -->
    <section class="container mt-8">
        <div class="card p-8" style="background: linear-gradient(135deg, rgba(0,200,150,0.15), rgba(7,17,14,0.9)); border: 1.5px solid var(--clr-primary); border-radius: var(--radius-xl); position: relative; overflow: hidden;">
            <div style="max-width: 600px; position: relative; z-index: 1;">
                <span class="badge badge-primary mb-3">Mulai Aksi Nyatamu</span>
                <h3 class="text-2xl font-bold mb-3">Punya barang tak terpakai atau makanan sisa layak konsumsi?</h3>
                <p class="text-muted text-sm mb-6" style="line-height: 1.7;">
                    Jangan biarkan menumpuk atau berakhir di tempat sampah. Unggah fotonya, AI Gemini kami akan menganalisis kondisi, memverifikasi tanggal kedaluwarsa, dan merekomendasikan harga terbaik dalam hitungan detik.
                </p>
                <div class="flex gap-3 flex-wrap">
                    <a href="/sell" class="btn btn-primary btn-lg">
                        <i data-lucide="camera" style="width: 18px; height: 18px;"></i>
                        <span>Jual Sekarang dengan AI</span>
                    </a>
                    <a href="/products" class="btn btn-outline btn-lg">
                        <span>Mulai Belanja Cerdas</span>
                    </a>
                </div>
            </div>
            <div style="position: absolute; right: 40px; bottom: -20px; opacity: 0.15; font-size: 180px; pointer-events: none;">
                🌿
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    let activeFilter = 'all';

    async function fetchHomeProducts() {
        const grid = document.getElementById('homeProductGrid');
        if (!grid) return;

        try {
            let url = `${API}/products?page=1`;
            if (activeFilter !== 'all') {
                url += `&type=${activeFilter}`;
            }

            const res = await fetch(url);
            if (!res.ok) throw new Error('Gagal memuat produk dari server.');
            const data = await res.json();
            const products = data.data || [];

            if (products.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <div class="empty-state-icon">📦</div>
                        <h3>Belum Ada Produk Tersedia</h3>
                        <p>Jadilah yang pertama mendaftarkan barang atau makanan surplus!</p>
                        <a href="/sell" class="btn btn-primary btn-sm mt-4">Jual Barang Sekarang</a>
                    </div>
                `;
                return;
            }

            grid.innerHTML = products.map(product => {
                const isFood = product.type === 'food';
                const badgeClass = isFood ? 'badge-warning' : 'badge-primary';
                const typeLabel = isFood ? 'Makanan Surplus' : 'Barang Preloved';
                const firstImg = product.images?.find(x => x.image_type === 'full_product')?.image_path || product.images?.[0]?.image_path;
                const photoSrc = imageUrl(firstImg);
                const sellerName = product.user?.name || 'Komunitas ReCircle';
                const categoryName = product.category?.name || typeLabel;

                return `
                    <article class="product-card" onclick="window.location.href='/products/${product.id}'">
                        <div style="position: relative;">
                            <img src="${photoSrc}" alt="${escapeHtml(product.title)}" class="product-card-img" loading="lazy">
                            <span class="badge ${badgeClass}" style="position: absolute; top: 10px; left: 10px; backdrop-filter: blur(4px);">
                                ${typeLabel}
                            </span>
                        </div>
                        <div class="product-card-body">
                            <div class="text-xs text-primary font-semibold mb-1 uppercase tracking-wider">${escapeHtml(categoryName)}</div>
                            <h3 class="product-card-name" title="${escapeHtml(product.title)}">${escapeHtml(product.title)}</h3>
                            <div class="product-card-price">${rupiah(product.system_price)}</div>
                            <div class="product-card-meta">
                                <span class="product-card-seller flex items-center gap-1">
                                    <i data-lucide="user" style="width: 12px; height: 12px;"></i>
                                    <span>${escapeHtml(sellerName)}</span>
                                </span>
                                <span>Detail →</span>
                            </div>
                        </div>
                    </article>
                `;
            }).join('');

            lucide.createIcons();
        } catch (err) {
            grid.innerHTML = `
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Koneksi Bermasalah</h3>
                    <p>${escapeHtml(err.message)}</p>
                    <button class="btn btn-outline btn-sm mt-3" onclick="fetchHomeProducts()">Coba Muat Ulang</button>
                </div>
            `;
        }
    }

    function filterType(type, button) {
        activeFilter = type;
        document.querySelectorAll('.category-chip').forEach(btn => btn.classList.remove('active'));
        if (button) button.classList.add('active');
        fetchHomeProducts();
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchHomeProducts();
    });
</script>
@endpush
