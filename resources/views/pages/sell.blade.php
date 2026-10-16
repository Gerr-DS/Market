@extends('layouts.app')

@section('title', 'Jual Produk — ReCircle')

@section('content')
<div class="page">
    <div class="container" style="max-width: 860px;">
        <!-- Header -->
        <div class="text-center mb-8">
            <span class="badge badge-primary mb-2">Listing Produk</span>
            <h1 class="text-2xl font-bold">Jual Barang & Selamatkan Makanan</h1>
            <p class="text-sm text-muted mt-1 max-width: 500px; margin: auto;">
                Tentukan sendiri harga jual barang dan makanan. Untuk makanan, masukkan tanggal kedaluwarsa sesuai label produk.
            </p>
        </div>

        <!-- Form Card Container -->
        <div class="card p-8">
            <!-- Sell Form -->
            <form id="sellStudioForm" class="flex flex-col gap-6">
                <!-- Product Type -->
                <div class="form-group">
                    <label class="form-label">Jenis Produk yang Ingin Dijual</label>
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label class="card p-4 flex items-center gap-3 cursor-pointer" style="border-color: var(--clr-primary); background: var(--clr-surface);" id="optTypeGoods">
                            <input type="radio" name="type" value="goods" checked onchange="handleTypeChange('goods')">
                            <div>
                                <strong class="text-sm block">Barang Preloved</strong>
                                <span class="text-xs text-muted">Elektronik, pakaian, perabot</span>
                            </div>
                        </label>
                        <label class="card p-4 flex items-center gap-3 cursor-pointer" style="background: var(--clr-surface);" id="optTypeFood">
                            <input type="radio" name="type" value="food" onchange="handleTypeChange('food')">
                            <div>
                                <strong class="text-sm block">Makanan Surplus</strong>
                                <span class="text-xs text-muted">Roti, kue, makanan berlebih</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Photos Upload Section -->
                <div class="form-group">
                    <label class="form-label">Foto Produk (1 sampai 6 foto foto jelas)</label>
                    <div class="upload-area" onclick="document.getElementById('inputProductImages').click()">
                        <input type="file" id="inputProductImages" name="images[]" multiple accept="image/*" style="display: none;" onchange="handleImagesSelected(this)">
                        <div class="upload-icon">📷</div>
                        <h4 class="text-sm font-semibold mb-1">Klik atau seret foto ke area ini</h4>
                        <p class="text-xs text-muted">Maksimal 6 foto (JPG, PNG, WebP hingga 10MB per foto)</p>
                    </div>
                    <div id="imagePreviewContainer" class="upload-images-preview"></div>
                </div>

                <!-- Product Name & Category -->
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Nama / Judul Produk</label>
                        <input type="text" name="title" class="form-input" required placeholder="Contoh: Blender Philips HR2115 2L" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" id="sellCategorySelect" class="form-input" required>
                            <option value="">Memuat kategori...</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label class="form-label">Deskripsi Lengkap & Kondisi</label>
                    <textarea name="description" class="form-input" rows="3" required placeholder="Jelaskan spesifikasi, fungsi, kelengkapan, alasan dijual..."></textarea>
                </div>

                <!-- Goods-only Fields -->
                <div id="goodsFields" class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Kondisi Penggunaan</label>
                        <select name="item_condition" class="form-input">
                            <option value="like_new">Seperti Baru (Mulus & Lengkap)</option>
                            <option value="minor_defect">Ada Cacat Ringan (Fungsi Normal)</option>
                            <option value="heavily_used">Sudah Banyak Dipakai</option>
                        </select>
                    </div>
                </div>

                <div id="sellerPriceField" class="form-group">
                    <label class="form-label">Harga Jual (Rp)</label>
                    <input type="number" name="seller_price" class="form-input" placeholder="Masukkan harga jual" min="1" step="1" required>
                    <small class="text-xs text-muted">Harga ini akan digunakan langsung sebagai harga produk.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Jumlah stok yang dijual</label>
                    <input type="number" name="stock" class="form-input" placeholder="Contoh: 5" min="1" max="1000000" step="1" required>
                    <small class="text-xs text-muted">Stok berkurang satu setiap ada pesanan baru.</small>
                </div>

                <!-- Food-only Fields -->
                <div id="foodFields" class="card p-4" style="display: none; background: rgba(255,165,2,0.06); border-color: rgba(255,165,2,0.3);">
                    <div class="text-xs font-bold uppercase tracking-wider text-warning mb-3 flex items-center gap-2">
                        <i data-lucide="alert-triangle" style="width: 15px; height: 15px;"></i>
                        <span>Ketentuan Khusus Makanan Surplus</span>
                    </div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Tanggal Kedaluwarsa</label>
                            <input type="date" id="expiryDateInput" name="expired_at" class="form-input">
                        </div>
                    </div>
                    <p class="text-xs text-muted mt-2">
                        Masukkan tanggal yang tercetak pada label. Produk dengan masa kedaluwarsa 30 hari atau kurang tidak dapat dijual.
                    </p>
                </div>

                <!-- Pickup Address & Leaflet Map Selector -->
                <div class="card p-5">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-muted mb-3 flex items-center gap-2">
                        <i data-lucide="map-pin" style="width: 15px; height: 15px; color: var(--clr-primary);"></i>
                        <span>Lokasi Titik Jemput Barang</span>
                    </h3>

                    <div class="form-group mb-3">
                        <label class="form-label">Alamat Lengkap Pengambilan</label>
                        <textarea name="pickup_address" class="form-input" rows="2" required placeholder="Jalan, nomor rumah/toko, patokan lokasi jemput"></textarea>
                    </div>

                    <div class="flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline btn-sm" onclick="toggleSellerMap()">
                            <i data-lucide="map" style="width: 14px; height: 14px;"></i>
                            <span>Pilih dari Peta Interaktif</span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="getDeviceLocation()">
                            <i data-lucide="navigation" style="width: 14px; height: 14px;"></i>
                            <span>Gunakan Lokasi GPS Saya</span>
                        </button>
                    </div>

                    <!-- Map Container -->
                    <div id="sellerMapContainer" style="height: 240px; border-radius: var(--radius-md); overflow: hidden; display: none; margin-bottom: 12px; border: 1px solid var(--clr-border);"></div>

                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                        <input type="number" id="sellerLat" name="pickup_latitude" step="0.0000001" class="form-input" placeholder="Latitude">
                        <input type="number" id="sellerLng" name="pickup_longitude" step="0.0000001" class="form-input" placeholder="Longitude">
                    </div>

                    <!-- RajaOngkir Area Picker for Origin -->
                    <div class="form-group mt-3">
                        <label class="form-label">Kota / Kecamatan Asal Pengiriman (Untuk Hitung Ongkir Kurir)</label>
                        <div class="flex gap-2">
                            <input type="text" id="sellerAreaInput" class="form-input" placeholder="Ketik minimal 3 huruf nama kota/kecamatan...">
                            <button type="button" class="btn btn-outline btn-sm" onclick="searchSellerArea()">Cari</button>
                        </div>
                        <select id="sellerAreaSelect" class="form-input mt-2" onchange="onSellerAreaSelected(this)">
                            <option value="">Pilih area yang cocok</option>
                        </select>
                        <input type="hidden" id="sellerAreaId" name="pickup_area_id">
                        <input type="hidden" id="sellerAreaLabel" name="pickup_area_label">
                        <div id="sellerAreaNotice" class="text-xs text-primary mt-1"></div>
                    </div>

                    <div class="form-group mt-3">
                        <label class="form-label">Perkiraan Berat Paket (Gram)</label>
                        <input type="number" name="weight_gram" class="form-input" value="1000" min="50" max="50000" required>
                    </div>
                </div>

                <div id="sellFormError" class="text-xs text-danger" style="display: none;"></div>

                <!-- Submit Button -->
                <button type="submit" id="btnSubmitAnalysis" class="btn btn-primary btn-lg w-full">
                    <i data-lucide="send" style="width: 18px; height: 18px;"></i>
                    <span>Periksa Produk</span>
                </button>
            </form>

            <!-- Product Review Results Panel -->
            <div id="aiResultPanel" class="flex flex-col gap-5 mt-6" style="display: none;">
                <div class="card p-5">
                <div class="ai-status">
                        <div class="ai-pulse"></div>
                        <strong class="text-base text-primary">Hasil Pemeriksaan Produk</strong>
                    </div>

                    <div id="aiAnalysisDetails" class="text-sm flex flex-col gap-2" style="line-height: 1.7;"></div>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="btnApproveProduct" class="btn btn-primary btn-lg flex-1" style="display: none;" onclick="approveAndPublishProduct()">
                        <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                        <span>Setujui & Terbitkan ke Etalase</span>
                    </button>
                    <button type="button" id="btnRejectProduct" class="btn btn-danger btn-lg flex-1" style="display: none;" onclick="rejectAndDeleteProduct()">
                        <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                        <span>Tidak Disetujui & Hapus</span>
                    </button>
                    <button type="button" class="btn btn-outline btn-lg" onclick="resetSellStudio()">
                        <span>Mulai Lagi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let sellerMap = null;
    let sellerMarker = null;
    let analyzedProduct = null;

    async function loadSellCategories() {
        const select = document.getElementById('sellCategorySelect');
        try {
            const res = await fetch(`${API}/categories`);
            if (res.ok) {
                const cats = await res.json();
                select.innerHTML = '<option value="">Pilih Kategori</option>' + cats.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            }
        } catch (e) {
            console.error('Category error', e);
        }
    }

    function handleTypeChange(type) {
        const goodsBox = document.getElementById('goodsFields');
        const foodBox = document.getElementById('foodFields');
        const optGoods = document.getElementById('optTypeGoods');
        const optFood = document.getElementById('optTypeFood');
        const submitLabel = document.querySelector('#btnSubmitAnalysis span');
        document.getElementById('expiryDateInput').required = type === 'food';

        if (type === 'food') {
            goodsBox.style.display = 'none';
            foodBox.style.display = 'block';
            optFood.style.borderColor = 'var(--clr-primary)';
            optGoods.style.borderColor = 'var(--clr-border)';
            submitLabel.textContent = 'Kirim Produk untuk Ditinjau';
        } else {
            goodsBox.style.display = 'grid';
            foodBox.style.display = 'none';
            optGoods.style.borderColor = 'var(--clr-primary)';
            optFood.style.borderColor = 'var(--clr-border)';
            submitLabel.textContent = 'Kirim Produk untuk Ditinjau';
        }
    }

    function handleImagesSelected(input) {
        const container = document.getElementById('imagePreviewContainer');
        container.innerHTML = '';
        const files = Array.from(input.files || []).slice(0, 6);

        files.forEach(file => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'upload-thumb';
            container.appendChild(img);
        });
    }

    function toggleSellerMap() {
        const box = document.getElementById('sellerMapContainer');
        box.style.display = (box.style.display === 'none' || !box.style.display) ? 'block' : 'none';

        if (box.style.display === 'block') {
            if (!sellerMap && window.L) {
                sellerMap = L.map('sellerMapContainer').setView([-6.2, 106.8], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(sellerMap);

                sellerMap.on('click', (e) => {
                    setCoordinates(e.latlng.lat, e.latlng.lng);
                });
            }
            setTimeout(() => sellerMap && sellerMap.invalidateSize(), 200);
        }
    }

    function setCoordinates(lat, lng) {
        document.getElementById('sellerLat').value = Number(lat).toFixed(7);
        document.getElementById('sellerLng').value = Number(lng).toFixed(7);

        if (sellerMap) {
            if (sellerMarker) {
                sellerMarker.setLatLng([lat, lng]);
            } else {
                sellerMarker = L.marker([lat, lng]).addTo(sellerMap);
            }
            sellerMap.setView([lat, lng], 15);
        }
    }

    function getDeviceLocation() {
        if (!navigator.geolocation) {
            showToast('GPS tidak didukung oleh browser Anda.', 'error');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                setCoordinates(pos.coords.latitude, pos.coords.longitude);
                showToast('Lokasi GPS berhasil diambil!', 'success');
            },
            () => showToast('Gagal mengakses lokasi GPS.', 'error'),
            { timeout: 8000 }
        );
    }

    async function searchSellerArea() {
        const term = document.getElementById('sellerAreaInput').value.trim();
        const notice = document.getElementById('sellerAreaNotice');
        const select = document.getElementById('sellerAreaSelect');

        if (term.length < 3) {
            notice.textContent = 'Ketik minimal 3 karakter nama kecamatan.';
            return;
        }

        notice.textContent = 'Mencari lokasi RajaOngkir...';
        try {
            const res = await fetch(`${API}/shipping/locations?search=${encodeURIComponent(term)}`, {
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token()}` }
            });
            const data = await res.json();
            const locs = data.locations || [];

            select.innerHTML = '<option value="">Pilih area yang cocok</option>' + locs.map(l => `
                <option value="${escapeHtml(l.id)}" data-label="${escapeHtml(l.label)}">${escapeHtml(l.label)}</option>
            `).join('');

            notice.textContent = locs.length ? `${locs.length} area ditemukan.` : 'Tidak ada lokasi yang cocok.';
        } catch (e) {
            notice.textContent = e.message;
        }
    }

    function onSellerAreaSelected(select) {
        const opt = select.selectedOptions[0];
        if (opt && opt.value) {
            document.getElementById('sellerAreaId').value = opt.value;
            document.getElementById('sellerAreaLabel').value = opt.dataset.label;
            document.getElementById('sellerAreaNotice').textContent = `Lokasi asal: ${opt.dataset.label}`;
        }
    }

    document.getElementById('sellStudioForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!token()) {
            openAuthModal('login');
            showToast('Masuk terlebih dahulu untuk menjual produk.', 'info');
            return;
        }

        const files = document.getElementById('inputProductImages').files;
        if (!files || files.length === 0) {
            showToast('Harap unggah minimal 1 foto produk.', 'error');
            return;
        }

        const btn = document.getElementById('btnSubmitAnalysis');
        const err = document.getElementById('sellFormError');
        err.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner" style="width: 16px; height: 16px;"></span> <span>Mengirim produk...</span>`;

        const formData = new FormData(e.currentTarget);

        try {
            const res = await fetch(`${API}/products/analyze`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: formData
            });

            const data = await res.json();
            analyzedProduct = data.product;

            if (!res.ok && !analyzedProduct) {
                throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Analisis produk gagal.');
            }

            // Display Results
            document.getElementById('sellStudioForm').style.display = 'none';
            const panel = document.getElementById('aiResultPanel');
            panel.style.display = 'flex';

            const isRejected = analyzedProduct?.status === 'rejected';
            const analysis = data.analysis || {};
            let detailsHtml = `
                <div>Produk: <strong>${escapeHtml(analyzedProduct?.title || '')}</strong></div>
                <div>Status: <strong class="${isRejected ? 'text-danger' : 'text-primary'}">${isRejected ? 'Tidak memenuhi syarat' : 'Menunggu persetujuan'}</strong></div>
            `;

            if (analysis.detected_date) {
                detailsHtml += `<div>Tanggal Kedaluwarsa: <strong class="text-warning">${escapeHtml(analysis.detected_date)}</strong></div>`;
            }
            if (analyzedProduct?.system_price) {
                detailsHtml += `<div class="text-base text-primary font-bold mt-2">Harga Jual yang Kamu Masukkan: ${rupiah(analyzedProduct.system_price)}</div>`;
            }
            if (data.rejection_reason) {
                detailsHtml += `<div class="text-xs text-danger mt-2">Catatan pemeriksaan: ${escapeHtml(data.rejection_reason)}</div>`;
            }

            document.getElementById('aiAnalysisDetails').innerHTML = detailsHtml;

            const btnApprove = document.getElementById('btnApproveProduct');
            const btnReject = document.getElementById('btnRejectProduct');
            if (!isRejected && analyzedProduct?.status === 'pending_approval') {
                btnApprove.style.display = 'inline-flex';
                btnReject.style.display = 'inline-flex';
            } else {
                btnApprove.style.display = 'none';
                btnReject.style.display = 'none';
            }

            showToast(isRejected ? 'Produk tidak memenuhi syarat.' : 'Produk siap ditinjau.', 'success');
            lucide.createIcons();
        } catch (error) {
            err.textContent = error.message;
            err.style.display = 'block';
            showToast(error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="send" style="width: 18px; height: 18px;"></i> <span>Kirim Produk untuk Ditinjau</span>`;
            lucide.createIcons();
        }
    });

    async function approveAndPublishProduct() {
        if (!analyzedProduct) return;
        const btn = document.getElementById('btnApproveProduct');
        btn.disabled = true;
        btn.textContent = 'Menerbitkan...';

        try {
            const res = await fetch(`${API}/products/${analyzedProduct.id}/approval`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ approved: true })
            });

            if (!res.ok) throw new Error('Gagal menyetujui produk.');

            showToast('Produk berhasil ditayangkan di pasar!', 'success');
            setTimeout(() => {
                window.location.href = '/dashboard/products';
            }, 1000);
        } catch (err) {
            showToast(err.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Setujui & Terbitkan ke Etalase';
        }
    }

    async function rejectAndDeleteProduct() {
        if (!analyzedProduct || !confirm('Produk ini dan seluruh foto serta data terkait akan dihapus. Lanjutkan?')) return;

        const btn = document.getElementById('btnRejectProduct');
        btn.disabled = true;
        try {
            const res = await fetch(`${API}/products/${analyzedProduct.id}/approval`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ approved: false })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Produk belum dapat dihapus.');

            analyzedProduct = null;
            document.getElementById('aiAnalysisDetails').innerHTML = '<div class="text-danger font-bold">Produk tidak disetujui dan seluruh data terkait telah dihapus.</div>';
            document.getElementById('btnApproveProduct').style.display = 'none';
            btn.style.display = 'none';
        } catch (error) {
            btn.disabled = false;
            showToast(error.message, 'error');
        }
    }

    function resetSellStudio() {
        document.getElementById('sellStudioForm').reset();
        document.getElementById('sellStudioForm').style.display = 'flex';
        document.getElementById('aiResultPanel').style.display = 'none';
        document.getElementById('imagePreviewContainer').innerHTML = '';
        analyzedProduct = null;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadSellCategories();
    });
</script>
@endpush
