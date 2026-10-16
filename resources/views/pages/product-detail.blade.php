@extends('layouts.app')

@section('title', 'Detail Produk — ReCircle')

@section('content')
<div class="page">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-muted mb-6">
            <a href="/" class="hover:text-primary">Beranda</a>
            <span>/</span>
            <a href="/products" class="hover:text-primary">Katalog</a>
            <span>/</span>
            <span id="breadcrumbTitle" class="text-primary truncate">Detail Produk</span>
        </div>

        <!-- Product Main Showcase -->
        <div id="productDetailContainer">
            <!-- Loading placeholder -->
            <div class="card p-8 flex items-center justify-center" style="min-height: 400px;">
                <div class="spinner spinner-lg"></div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 620px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Edit Postingan</h3>
                <p class="text-xs text-muted mt-1">Perbarui informasi dan harga postingan Anda.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditProductModal()">✕</button>
        </div>
        <form id="editProductForm" class="flex flex-col gap-4">
            <div class="form-group">
                <label class="form-label">Nama produk</label>
                <input type="text" name="title" id="editProductTitle" class="form-input" maxlength="255" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" id="editProductDescription" class="form-input" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Harga jual (Rp)</label>
                <input type="number" name="seller_price" id="editProductPrice" class="form-input" min="1" step="1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Jumlah stok</label>
                <input type="number" name="stock" id="editProductStock" class="form-input" min="0" max="1000000" step="1" required>
                <small class="text-xs text-muted">Isi 0 untuk menyembunyikan produk dari katalog. Tambahkan stok untuk menayangkannya kembali.</small>
            </div>
            <div id="editConditionGroup" class="form-group">
                <label class="form-label">Kondisi barang</label>
                <select name="item_condition" id="editProductCondition" class="form-input">
                    <option value="like_new">Seperti baru</option>
                    <option value="minor_defect">Ada cacat ringan</option>
                    <option value="heavily_used">Sudah banyak dipakai</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat pengambilan</label>
                <textarea name="pickup_address" id="editProductPickupAddress" class="form-input" rows="2" maxlength="1000" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Berat paket (gram)</label>
                <input type="number" name="weight_gram" id="editProductWeight" class="form-input" min="1" max="50000" required>
            </div>
            <div id="editProductError" class="text-xs text-danger" style="display: none;"></div>
            <button type="submit" id="saveProductEdit" class="btn btn-primary btn-lg w-full">Simpan Perubahan</button>
        </form>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 580px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Konfirmasi & Pembelian</h3>
                <p class="text-xs text-muted mt-1">Pembayaran aman dengan saldo escrow ReCircle.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeCheckoutModal()">✕</button>
        </div>

        <div id="checkoutProductSummary" class="card p-4 mb-4" style="background: var(--clr-surface);"></div>

        <form id="checkoutForm" class="flex flex-col gap-4">
            <div class="form-group">
                <label class="form-label">Metode Pengambilan / Pengiriman</label>
                <select id="checkoutFulfillment" name="fulfillment_type" class="form-input" onchange="toggleFulfillmentFields(this.value)">
                    <option value="direct_deal">Ambil Langsung / Atur dengan Penjual (COD)</option>
                    <option value="expedition">Kirim via Kurir Ekspedisi</option>
                </select>
            </div>

            <!-- Expedition Fields -->
            <div id="expeditionFields" class="flex flex-col gap-3" style="display: none; border-top: 1px solid var(--clr-border); padding-top: 16px;">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap Penerima</label>
                    <input type="text" id="buyerRecipientName" class="form-input" placeholder="Nama penerima paket">
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp Penerima</label>
                    <input type="tel" id="buyerRecipientPhone" class="form-input" placeholder="08xxxxxxxxxx">
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Lengkap Tujuan</label>
                    <textarea id="buyerFullAddress" class="form-input" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, patokan"></textarea>
                </div>

                <!-- Location area search for RajaOngkir -->
                <div class="form-group">
                    <label class="form-label">Kecamatan / Kota Tujuan Ongkir</label>
                    <div class="flex gap-2">
                        <input type="text" id="buyerAreaSearchTerm" class="form-input" placeholder="Ketik min. 3 huruf kecamatan...">
                        <button type="button" class="btn btn-outline btn-sm" onclick="searchBuyerArea()">Cari</button>
                    </div>
                    <select id="buyerAreaSelect" class="form-input mt-2" onchange="onBuyerAreaSelected(this)">
                        <option value="">Pilih dari hasil pencarian lokasi</option>
                    </select>
                    <div id="buyerAreaNotice" class="text-xs text-primary mt-1"></div>
                </div>

                <button type="button" id="btnCalcShipping" class="btn btn-outline w-full" onclick="calculateShippingRates()">
                    <i data-lucide="truck" style="width: 14px; height: 14px;"></i>
                    <span>Cek Pilihan Layanan Kurir & Tarif</span>
                </button>

                <div class="form-group">
                    <label class="form-label">Pilih Layanan Kurir</label>
                    <select id="shippingQuoteSelect" name="shipping_quote_id" class="form-input" disabled onchange="updateCheckoutTotal(this)">
                        <option value="">Hitung tarif pengiriman terlebih dahulu</option>
                    </select>
                </div>
            </div>

            <!-- Total Price Calculation -->
            <div id="checkoutTotalBox" class="card p-4 mt-2" style="background: rgba(0,200,150,0.06); border-color: rgba(0,200,150,0.25);">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-muted">Harga Barang</span>
                    <strong id="summaryItemPrice">Rp0</strong>
                </div>
                <div id="summaryShippingRow" class="flex justify-between text-sm mb-1" style="display: none;">
                    <span class="text-muted">Biaya Ongkir</span>
                    <strong id="summaryShippingPrice">Rp0</strong>
                </div>
                <div class="divider" style="margin: 8px 0;"></div>
                <div class="flex justify-between text-base">
                    <span class="font-bold">Total Pembayaran</span>
                    <strong id="summaryTotalPrice" class="text-primary font-bold text-lg">Rp0</strong>
                </div>
            </div>

            <div id="checkoutErrorNotice" class="text-xs text-danger" style="display: none;"></div>

            <button type="submit" id="btnSubmitCheckout" class="btn btn-primary btn-lg w-full mt-2">
                <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                <span>Konfirmasi & Buat Pesanan</span>
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const productId = window.location.pathname.split('/').filter(Boolean).pop();
    let currentProduct = null;
    let selectedQuotePrice = 0;
    let selectedArea = null;
    let isProductOwner = false;

    async function loadProductDetail() {
        const container = document.getElementById('productDetailContainer');
        try {
            const res = await fetch(`${API}/products/${encodeURIComponent(productId)}`, {
                headers: token() ? { 'Authorization': `Bearer ${token()}` } : {}
            });
            if (!res.ok) throw new Error('Gagal memuat katalog produk.');
            currentProduct = await res.json();
            if (!currentProduct) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">❌</div>
                        <h3>Produk Tidak Ditemukan</h3>
                        <p>Produk mungkin telah terjual atau dihapus oleh penjual.</p>
                        <a href="/products" class="btn btn-primary btn-sm mt-4">Kembali ke Katalog</a>
                    </div>
                `;
                return;
            }

            isProductOwner = false;
            if (token()) {
                try {
                    const profileResponse = await fetch(`${API}/user/profile`, {
                        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token()}` }
                    });
                    if (profileResponse.ok) {
                        const profile = await profileResponse.json();
                        isProductOwner = Number(profile.id) === Number(currentProduct.user_id);
                    }
                } catch (_) {}
            }

            document.getElementById('breadcrumbTitle').textContent = currentProduct.title;

            const isFood = currentProduct.type === 'food';
            const typeLabel = isFood ? 'Makanan Surplus' : 'Barang Preloved';
            const images = currentProduct.images || [];
            const primaryImage = images[0]?.image_path || '';
            const seller = currentProduct.user || {};

            const conditionMap = {
                like_new: 'Seperti Baru (Sangat Terawat)',
                minor_defect: 'Cacat Ringan (Fungsi Normal)',
                heavily_used: 'Sudah Banyak Dipakai'
            };
            const conditionLabel = conditionMap[currentProduct.item_condition] || 'Kondisi Baik Terawat';

            container.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 36px; align-items: start;">
                    <!-- Photos Column -->
                    <div>
                        <div class="card p-2" style="background: var(--clr-card); overflow: hidden; border-radius: var(--radius-xl);">
                            <img id="mainProductPhoto" src="${imageUrl(primaryImage)}" alt="${escapeHtml(currentProduct.title)}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: var(--radius-lg);">
                        </div>
                        
                        ${images.length > 1 ? `
                            <div class="flex gap-2 mt-3 overflow-x-auto pb-2">
                                ${images.map((img, idx) => `
                                    <img src="${imageUrl(img.image_path)}" class="upload-thumb" style="cursor: pointer; ${idx === 0 ? 'border-color: var(--clr-primary);' : ''}" onclick="switchMainPhoto('${imageUrl(img.image_path)}', this)">
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>

                    <!-- Details & Purchase Column -->
                    <div class="flex flex-col gap-5">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge ${isFood ? 'badge-warning' : 'badge-primary'}">${typeLabel}</span>
                                <span class="badge badge-success flex items-center gap-1">
                                    <i data-lucide="sparkles" style="width: 12px; height: 12px;"></i>
                                    <span>Produk Terverifikasi</span>
                                </span>
                            </div>
                            <h1 class="text-2xl font-bold mb-2">${escapeHtml(currentProduct.title)}</h1>
                            <div class="text-3xl font-extrabold text-primary mb-1">
                                ${rupiah(currentProduct.system_price)}
                            </div>
                            <div class="text-xs text-muted">
                                Kategori: <strong class="text-dim">${escapeHtml(currentProduct.category?.name || 'Umum')}</strong>
                            </div>
                        </div>

                        <!-- Product Condition Assessment Card -->
                        <div class="card p-4" style="background: rgba(0,200,150,0.05); border-color: rgba(0,200,150,0.2);">
                            <div class="text-xs font-bold uppercase tracking-wider text-primary mb-2 flex items-center gap-2">
                                <i data-lucide="check-circle-2" style="width: 15px; height: 15px;"></i>
                                <span>Laporan Kelayakan Produk</span>
                            </div>
                            <div class="text-sm flex flex-col gap-1">
                                <div>Kondisi Barang: <strong>${conditionLabel}</strong></div>
                                ${currentProduct.expired_at ? `<div>Tanggal Kedaluwarsa: <strong class="text-warning">${currentProduct.expired_at}</strong></div>` : ''}
                                <div>Berat Paket Kurir: <strong>${currentProduct.weight_gram || 1000} gram</strong></div>
                                ${isProductOwner ? `<div>Stok tersedia: <strong>${currentProduct.stock ?? 0}</strong></div>` : ''}
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="card p-5">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-muted mb-3">Deskripsi Produk</h3>
                            <p class="text-sm" style="line-height: 1.8; white-space: pre-line;">${escapeHtml(currentProduct.description)}</p>
                        </div>

                        <!-- Seller Info Card -->
                        <div class="card p-4 flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-3">
                                <div class="nav-avatar" style="width: 44px; height: 44px; font-size: 16px;">
                                    ${(seller.name || 'P').charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <div class="text-xs text-muted">Dijual oleh</div>
                                    <div class="font-bold text-sm">${escapeHtml(seller.name || 'Penjual Terpercaya')}</div>
                                    <div class="text-xs text-dim">${escapeHtml(currentProduct.pickup_address || 'Lokasi terverifikasi')}</div>
                                </div>
                            </div>
                            ${isProductOwner ? '' : `<button type="button" class="btn btn-outline btn-sm" onclick="startDirectChat(${currentProduct.id})">
                                <i data-lucide="message-circle" style="width: 15px; height: 15px;"></i>
                                <span>Chat Penjual</span>
                            </button>`}
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 mt-2">
                            ${isProductOwner ? `
                            <button type="button" class="btn btn-outline btn-lg flex-1" onclick="openEditProductModal()">
                                <i data-lucide="pencil" style="width: 18px; height: 18px;"></i>
                                <span>Edit Postingan</span>
                            </button>
                            <button type="button" class="btn btn-danger btn-lg flex-1" onclick="deleteProductPost()">
                                <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                                <span>Hapus Postingan</span>
                            </button>
                            ` : `
                            <button type="button" class="btn btn-primary btn-lg flex-1" onclick="openCheckoutModal()">
                                <i data-lucide="shopping-bag" style="width: 18px; height: 18px;"></i>
                                <span>Beli Sekarang</span>
                            </button>
                            `}
                        </div>
                    </div>
                </div>
            `;

            lucide.createIcons();
        } catch (e) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Gagal Memuat Detail</h3>
                    <p>${escapeHtml(e.message)}</p>
                </div>
            `;
        }
    }

    function switchMainPhoto(src, el) {
        document.getElementById('mainProductPhoto').src = src;
        document.querySelectorAll('.upload-thumb').forEach(t => t.style.borderColor = 'var(--clr-border)');
        if (el) el.style.borderColor = 'var(--clr-primary)';
    }

    function openEditProductModal() {
        if (!isProductOwner || !currentProduct) return;
        document.getElementById('editProductTitle').value = currentProduct.title || '';
        document.getElementById('editProductDescription').value = currentProduct.description || '';
        document.getElementById('editProductPrice').value = currentProduct.seller_price || currentProduct.system_price || '';
        document.getElementById('editProductStock').value = currentProduct.stock ?? 0;
        document.getElementById('editProductCondition').value = currentProduct.item_condition || 'like_new';
        document.getElementById('editConditionGroup').style.display = currentProduct.type === 'food' ? 'none' : 'flex';
        document.getElementById('editProductPickupAddress').value = currentProduct.pickup_address || '';
        document.getElementById('editProductWeight').value = currentProduct.weight_gram || 1000;
        document.getElementById('editProductError').style.display = 'none';
        document.getElementById('editProductModal').style.display = 'flex';
    }

    function closeEditProductModal() {
        document.getElementById('editProductModal').style.display = 'none';
    }

    async function deleteProductPost() {
        if (!isProductOwner || !currentProduct || !confirm('Hapus postingan ini beserta foto dan seluruh data terkait?')) return;
        try {
            const response = await fetch(`${API}/products/${currentProduct.id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token()}` }
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Postingan belum dapat dihapus.');
            showToast('Postingan berhasil dihapus.', 'success');
            window.location.href = '/dashboard/products';
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    document.getElementById('editProductForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!isProductOwner || !currentProduct) return;
        const button = document.getElementById('saveProductEdit');
        const errorBox = document.getElementById('editProductError');
        const payload = Object.fromEntries(new FormData(event.currentTarget));
        if (currentProduct.type === 'food') payload.item_condition = null;
        button.disabled = true;
        errorBox.style.display = 'none';
        try {
            const response = await fetch(`${API}/products/${currentProduct.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token()}` },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Perubahan belum dapat disimpan.');
            closeEditProductModal();
            showToast('Postingan berhasil diperbarui.', 'success');
            await loadProductDetail();
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.style.display = 'block';
        } finally {
            button.disabled = false;
        }
    });

    function openCheckoutModal() {
        if (!token()) {
            openAuthModal('login');
            showToast('Silakan masuk terlebih dahulu untuk membeli.', 'info');
            return;
        }

        const summary = document.getElementById('checkoutProductSummary');
        summary.innerHTML = `
            <div class="flex items-center gap-3">
                <img src="${imageUrl(currentProduct.images?.[0]?.image_path)}" class="upload-thumb" style="width: 56px; height: 56px;">
                <div>
                    <h4 class="font-semibold text-sm truncate" style="max-width: 320px;">${escapeHtml(currentProduct.title)}</h4>
                    <div class="text-primary font-bold text-base">${rupiah(currentProduct.system_price)}</div>
                </div>
            </div>
        `;

        document.getElementById('summaryItemPrice').textContent = rupiah(currentProduct.system_price);
        document.getElementById('summaryTotalPrice').textContent = rupiah(currentProduct.system_price);
        document.getElementById('checkoutModal').style.display = 'flex';
        lucide.createIcons();
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutModal').style.display = 'none';
    }

    function toggleFulfillmentFields(val) {
        const fields = document.getElementById('expeditionFields');
        const shipRow = document.getElementById('summaryShippingRow');
        if (val === 'expedition') {
            fields.style.display = 'flex';
            shipRow.style.display = 'flex';
        } else {
            fields.style.display = 'none';
            shipRow.style.display = 'none';
            selectedQuotePrice = 0;
            document.getElementById('summaryTotalPrice').textContent = rupiah(currentProduct.system_price);
        }
    }

    async function searchBuyerArea() {
        const term = document.getElementById('buyerAreaSearchTerm').value.trim();
        const notice = document.getElementById('buyerAreaNotice');
        const select = document.getElementById('buyerAreaSelect');

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

            select.innerHTML = '<option value="">Pilih lokasi yang cocok</option>' + locs.map(l => `
                <option value="${escapeHtml(l.id)}" data-label="${escapeHtml(l.label)}">${escapeHtml(l.label)}</option>
            `).join('');

            notice.textContent = locs.length ? `${locs.length} lokasi ditemukan.` : 'Tidak ada lokasi yang cocok.';
        } catch (e) {
            notice.textContent = e.message;
        }
    }

    function onBuyerAreaSelected(select) {
        const opt = select.selectedOptions[0];
        if (opt && opt.value) {
            selectedArea = { id: opt.value, label: opt.dataset.label };
            document.getElementById('buyerAreaNotice').textContent = `Terpilih: ${opt.dataset.label}`;
        }
    }

    async function calculateShippingRates() {
        const quoteSelect = document.getElementById('shippingQuoteSelect');
        const recipientName = document.getElementById('buyerRecipientName').value.trim();
        const recipientPhone = document.getElementById('buyerRecipientPhone').value.trim();
        const fullAddress = document.getElementById('buyerFullAddress').value.trim();

        if (!recipientName || !recipientPhone || !fullAddress || !selectedArea) {
            showToast('Lengkapi data penerima dan pilih kecamatan tujuan.', 'error');
            return;
        }

        quoteSelect.disabled = true;
        quoteSelect.innerHTML = '<option value="">Menghubungi API kurir...</option>';

        try {
            const res = await fetch(`${API}/shipping/quotes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({
                    product_id: currentProduct.id,
                    address: {
                        recipient_name: recipientName,
                        phone: recipientPhone,
                        full_address: fullAddress,
                        area_id: selectedArea.id,
                        area_label: selectedArea.label
                    }
                })
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menghitung ongkir.');

            const quotes = data.quotes || [];
            quoteSelect.innerHTML = '<option value="">Pilih jasa kirim</option>' + quotes.map(q => `
                <option value="${q.id}" data-price="${q.price}">
                    ${escapeHtml(q.courier_name)} ${escapeHtml(q.service_name)} · ${rupiah(q.price)} (${q.duration || 'Estimasi 2-3 hari'})
                </option>
            `).join('');

            quoteSelect.disabled = false;
            if (quotes.length === 1) {
                quoteSelect.value = quotes[0].id;
                quoteSelect.dispatchEvent(new Event('change'));
            }
            showToast('Tarif kurir berhasil dihitung!', 'success');
        } catch (err) {
            quoteSelect.innerHTML = `<option value="">Error: ${err.message}</option>`;
            showToast(err.message, 'error');
        }
    }

    function updateCheckoutTotal(select) {
        const opt = select.selectedOptions[0];
        selectedQuotePrice = opt?.dataset.price ? Number(opt.dataset.price) : 0;
        document.getElementById('summaryShippingPrice').textContent = rupiah(selectedQuotePrice);
        const total = Number(currentProduct.system_price || 0) + selectedQuotePrice;
        document.getElementById('summaryTotalPrice').textContent = rupiah(total);
    }

    document.getElementById('checkoutForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('checkoutErrorNotice');
        const btn = document.getElementById('btnSubmitCheckout');
        err.style.display = 'none';

        const fulfillment = document.getElementById('checkoutFulfillment').value;
        const payload = {
            product_id: currentProduct.id,
            fulfillment_type: fulfillment
        };

        if (fulfillment === 'expedition') {
            const quoteId = document.getElementById('shippingQuoteSelect').value;
            if (!quoteId) {
                err.textContent = 'Harap pilih layanan kurir terlebih dahulu.';
                err.style.display = 'block';
                return;
            }
            payload.shipping_quote_id = Number(quoteId);
        }

        btn.disabled = true;
        btn.textContent = 'Membuat Pesanan...';

        try {
            const res = await fetch(`${API}/orders/checkout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal memproses pesanan.');

            closeCheckoutModal();
            showToast(`Pesanan #${data.order_number} berhasil dibuat!`, 'success');
            setTimeout(() => {
                window.location.href = '/dashboard/orders';
            }, 1000);
        } catch (error) {
            err.textContent = error.message;
            err.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Konfirmasi & Buat Pesanan';
        }
    });

    function startDirectChat(prodId) {
        if (!token()) {
            openAuthModal('login');
            return;
        }
        window.location.href = `/dashboard/chat?product_id=${prodId}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadProductDetail();
    });
</script>
@endpush
