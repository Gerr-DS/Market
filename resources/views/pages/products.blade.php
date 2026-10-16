@extends('layouts.app')

@section('title', 'Katalog Produk — ReCircle')

@section('content')
<div class="page">
    <div class="container">
        <!-- Breadcrumb & Header -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold mb-1">Eksplorasi Pasar Sirkular</h1>
                <p class="text-sm text-muted">Beli makanan surplus & barang preloved langsung dari penjual terpercaya.</p>
            </div>
            <div id="productTotalCounter" class="text-xs text-muted font-semibold badge badge-muted">
                Memuat produk...
            </div>
        </div>

        <!-- Catalog Layout: Filters Sidebar + Grid -->
        <div class="flex gap-8 flex-col md:flex-row">
            <!-- Filter Sidebar -->
            <aside class="filter-sidebar">
                <div class="card p-6">
                    <h3 class="text-sm font-bold uppercase tracking-wider mb-4 flex items-center justify-between">
                        <span>Filter Produk</span>
                        <button type="button" class="btn-ghost text-xs text-primary" onclick="resetFilters()">Reset</button>
                    </h3>

                    <!-- Search Filter -->
                    <div class="filter-group">
                        <label class="filter-group-title">Kata Kunci</label>
                        <input type="text" id="catalogSearchInput" class="form-input" placeholder="Cari barang..." value="{{ request('q') }}">
                    </div>

                    <!-- Type Filter -->
                    <div class="filter-group">
                        <div class="filter-group-title">Jenis Produk</div>
                        <div class="flex flex-col gap-1">
                            <label class="filter-option {{ !request('type') || request('type') === 'all' ? 'active' : '' }}">
                                <input type="radio" name="product_type" value="all" {{ !request('type') || request('type') === 'all' ? 'checked' : '' }} onchange="applyFilters()">
                                <span>Semua Kategori</span>
                            </label>
                            <label class="filter-option {{ request('type') === 'food' ? 'active' : '' }}">
                                <input type="radio" name="product_type" value="food" {{ request('type') === 'food' ? 'checked' : '' }} onchange="applyFilters()">
                                <span>Makanan Surplus</span>
                            </label>
                            <label class="filter-option {{ request('type') === 'goods' ? 'active' : '' }}">
                                <input type="radio" name="product_type" value="goods" {{ request('type') === 'goods' ? 'checked' : '' }} onchange="applyFilters()">
                                <span>Barang Preloved</span>
                            </label>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <div class="filter-group-title">Kategori Barang</div>
                        <select id="catalogCategorySelect" class="form-input" onchange="applyFilters()">
                            <option value="">Semua Kategori</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-primary w-full mt-4" onclick="applyFilters()">
                        <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </aside>

            <!-- Main Catalog Product List -->
            <div style="flex: 1;">
                <div id="catalogGrid" class="product-grid">
                    <!-- Skeletons -->
                    <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                        <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                        <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
                    </div>
                    <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                        <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                        <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
                    </div>
                    <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                        <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                        <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
                    </div>
                </div>

                <!-- Pagination area -->
                <div id="catalogPagination" class="flex justify-center gap-2 mt-8"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let catalogPage = 1;

    async function loadCategories() {
        const select = document.getElementById('catalogCategorySelect');
        try {
            const res = await fetch(`${API}/categories`);
            if (res.ok) {
                const cats = await res.json();
                select.innerHTML = '<option value="">Semua Kategori</option>' + cats.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            }
        } catch (e) {
            console.error('Categories load error', e);
        }
    }

    async function fetchCatalogProducts() {
        const grid = document.getElementById('catalogGrid');
        const counter = document.getElementById('productTotalCounter');
        const pagination = document.getElementById('catalogPagination');

        grid.innerHTML = `
            <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
            </div>
            <div class="card p-4 flex flex-col gap-3" style="height: 320px; animation: pulse 1.5s infinite;">
                <div style="background: var(--clr-surface); height: 180px; border-radius: 8px;"></div>
                <div style="background: var(--clr-surface); height: 20px; width: 60%; border-radius: 4px;"></div>
            </div>
        `;

        const queryParams = new URLSearchParams();
        queryParams.set('page', catalogPage);

        const search = document.getElementById('catalogSearchInput').value.trim();
        if (search) queryParams.set('q', search);

        const selectedType = document.querySelector('input[name="product_type"]:checked')?.value || 'all';
        if (selectedType !== 'all') queryParams.set('type', selectedType);

        const categoryId = document.getElementById('catalogCategorySelect').value;
        if (categoryId) queryParams.set('category_id', categoryId);

        try {
            const res = await fetch(`${API}/products?${queryParams.toString()}`);
            if (!res.ok) throw new Error('Gagal memuat produk dari server.');
            const json = await res.json();
            const items = json.data || [];

            counter.textContent = `${json.total || items.length} Produk Ditemukan`;

            if (items.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <div class="empty-state-icon">🔍</div>
                        <h3>Tidak Ada Produk yang Cocok</h3>
                        <p>Coba ubah kata kunci atau bersihkan filter pencarianmu.</p>
                        <button class="btn btn-outline btn-sm mt-3" onclick="resetFilters()">Reset Filter</button>
                    </div>
                `;
                pagination.innerHTML = '';
                return;
            }

            grid.innerHTML = items.map(product => {
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
                                <span>Beli Sekarang →</span>
                            </div>
                        </div>
                    </article>
                `;
            }).join('');

            // Pagination Controls
            if (json.last_page > 1) {
                let paginationHtml = '';
                for (let i = 1; i <= json.last_page; i++) {
                    const isActive = (i === json.current_page);
                    paginationHtml += `
                        <button type="button" class="btn ${isActive ? 'btn-primary' : 'btn-ghost'} btn-sm" onclick="goToPage(${i})">
                            ${i}
                        </button>
                    `;
                }
                pagination.innerHTML = paginationHtml;
            } else {
                pagination.innerHTML = '';
            }

            lucide.createIcons();
        } catch (error) {
            grid.innerHTML = `
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Gagal Mengambil Data</h3>
                    <p>${escapeHtml(error.message)}</p>
                </div>
            `;
        }
    }

    function applyFilters() {
        catalogPage = 1;
        fetchCatalogProducts();
    }

    function resetFilters() {
        document.getElementById('catalogSearchInput').value = '';
        document.querySelector('input[name="product_type"][value="all"]').checked = true;
        document.getElementById('catalogCategorySelect').value = '';
        catalogPage = 1;
        fetchCatalogProducts();
    }

    function goToPage(page) {
        catalogPage = page;
        fetchCatalogProducts();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.getElementById('catalogSearchInput')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') applyFilters();
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadCategories();
        fetchCatalogProducts();
    });
</script>
@endpush
