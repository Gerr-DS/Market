@extends('layouts.app')

@section('title', 'Pesanan Saya (Pembeli) — ReCircle')

@section('content')
<div class="page">
    <div class="container">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold">Pesanan Saya</h1>
                <p class="text-sm text-muted">Pantau status pengiriman atau pengambilan barang yang Anda beli.</p>
            </div>
            <a href="/products" class="btn btn-outline btn-sm">
                <i data-lucide="shopping-bag" style="width: 15px; height: 15px;"></i>
                <span>Jelajahi Produk Lain</span>
            </a>
        </div>

        <!-- Navigation Tabs for Dashboard -->
        <div class="tabs">
            <a href="/dashboard" class="tab">Ringkasan</a>
            <a href="/dashboard/products" class="tab">Produk Saya</a>
            <a href="/dashboard/sales" class="tab">Pesanan Masuk (Penjual)</a>
            <a href="/dashboard/orders" class="tab active">Pesanan Saya (Pembeli)</a>
            <a href="/dashboard/wallet" class="tab">Dompet & Saldo</a>
            <a href="/dashboard/chat" class="tab">Pesan Chat</a>
        </div>

        <!-- Buyer Orders Container -->
        <div id="buyerOrdersContainer" class="flex flex-col gap-4">
            <div class="card p-8 flex justify-center items-center">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 620px;">
        <div class="modal-header">
            <div><h3 class="modal-title">Detail Pesanan</h3><p id="orderDetailNumber" class="text-xs text-muted mt-1"></p></div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeOrderDetails()">✕</button>
        </div>
        <div id="orderDetailContent" class="flex flex-col gap-3"></div>
        <div id="orderDetailActions" class="flex gap-2 mt-4"></div>
    </div>
</div>

<!-- Report Issue Modal -->
<div id="reportModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Laporkan Kendala Pesanan</h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeReportModal()">✕</button>
        </div>
        <form id="reportOrderForm" class="flex flex-col gap-4">
            <input type="hidden" id="reportOrderId">
            <div class="form-group">
                <label class="form-label">Alasan Pelaporan / Kendala</label>
                <select id="reportReason" class="form-input" required>
                    <option value="item_not_received">Barang tidak kunjung diterima / penjual tidak merespons</option>
                    <option value="item_not_as_described">Kondisi barang tidak sesuai dengan deskripsi/foto</option>
                    <option value="item_damaged_or_spoiled">Barang rusak atau makanan basi / lewat kedaluwarsa</option>
                    <option value="other">Alasan lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Penjelasan Rinci</label>
                <textarea id="reportExplanation" class="form-input" rows="3" required placeholder="Jelaskan kendala secara jelas untuk ditindaklanjuti admin..."></textarea>
            </div>
            <button type="submit" id="btnSubmitReport" class="btn btn-danger w-full">Kirim Laporan ke Admin</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadBuyerOrders() {
        if (!token()) {
            openAuthModal('login');
            return;
        }

        const container = document.getElementById('buyerOrdersContainer');
        try {
            const res = await fetch(`${API}/user/orders`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });

            if (!res.ok) throw new Error('Gagal memuat pesanan saya.');
            const json = await res.json();
            const orders = json.data || [];
            window._buyerOrders = orders;

            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🛍️</div>
                        <h3>Belum Ada Riwayat Belanja</h3>
                        <p>Temukan makanan surplus atau barang preloved yang kamu butuhkan hari ini.</p>
                        <a href="/products" class="btn btn-primary btn-sm mt-4">Mulai Belanja</a>
                    </div>
                `;
                return;
            }

            const statusLabels = {
                pending: '<span class="badge badge-warning">Menunggu Konfirmasi Penjual</span>',
                processing: '<span class="badge badge-info">Sedang Disiapkan Penjual</span>',
                shipped: '<span class="badge badge-info">Dalam Pengiriman Kurir</span>',
                delivered: '<span class="badge badge-primary">Barang Sudah Tiba</span>',
                completed: '<span class="badge badge-success">Pesanan Selesai</span>',
                cancelled: '<span class="badge badge-danger">Pesanan Dibatalkan</span>',
                disputed: '<span class="badge badge-danger">Dalam Tinjauan Sengketa</span>'
            };
            const paymentLabels = {
                unpaid: 'Belum dibayar',
                paid_escrow: 'Sudah dibayar · dana ditahan',
                released: 'Pembayaran selesai',
                refunded: 'Saldo dikembalikan'
            };

            container.innerHTML = orders.map(order => {
                const p = order.product || {};
                const img = p.images?.[0]?.image_path;
                const isDirect = order.fulfillment_type === 'direct_deal';
                const isClosed = ['completed', 'cancelled'].includes(order.order_status);
                const orderStatusLabel = statusLabels[order.order_status] || '';
                const paymentLabel = isDirect && order.payment_status === 'unpaid'
                    ? 'Pembayaran diatur langsung dengan penjual'
                    : (paymentLabels[order.payment_status] || order.payment_status || '');

                let actions = '';
                if (!isClosed) {
                    actions += `
                        <button type="button" class="btn btn-outline btn-sm" onclick="chatWithSeller(${order.id})">
                            <i data-lucide="message-square" style="width: 14px; height: 14px;"></i>
                            <span>Chat Penjual</span>
                        </button>
                    `;
                }

                if (['pending', 'processing'].includes(order.order_status)) {
                    actions += `
                        <button type="button" class="btn btn-danger btn-sm" onclick="cancelBuyerOrder(${order.id})">
                            Batalkan
                        </button>
                    `;
                }

                if (!isDirect && order.order_status === 'pending' && order.payment_status === 'unpaid') {
                    actions += `
                        <button type="button" class="btn btn-primary btn-sm" onclick="payBuyerOrder(${order.id})">
                            <i data-lucide="wallet" style="width: 14px; height: 14px;"></i>
                            <span>Bayar dari Saldo</span>
                        </button>
                    `;
                }

                if (['shipped', 'delivered'].includes(order.order_status)) {
                    actions += `
                        <button type="button" class="btn btn-primary btn-sm" onclick="confirmBuyerOrder(${order.id})">
                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                            <span>Konfirmasi Selesai / Diterima</span>
                        </button>
                    `;
                    if (!isDirect || order.payment_status === 'paid_escrow') {
                        actions += `<button type="button" class="btn btn-danger btn-sm" onclick="openReportModal(${order.id})">Laporkan Masalah</button>`;
                    }
                }

                return `
                    <div class="card p-5 flex items-center justify-between flex-wrap gap-4" style="background: var(--clr-card);">
                        <div class="flex items-center gap-4">
                            <img src="${imageUrl(img)}" class="upload-thumb" style="width: 72px; height: 72px; border-radius: var(--radius-md);">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold text-muted">${escapeHtml(order.order_number)}</span>
                                    ${orderStatusLabel}
                                    <span class="badge badge-muted">${isDirect ? 'Ambil Langsung (COD)' : 'Ekspedisi Kurir'}</span>
                                </div>
                                <h3 class="font-bold text-base mb-1">${escapeHtml(p.title || 'Barang Pesanan')}</h3>
                                <div class="text-xs text-muted mb-1">
                                    Penjual: <strong>${escapeHtml(p.user?.name || 'Komunitas ReCircle')}</strong>
                                </div>
                                <div class="text-primary font-bold text-base">${rupiah(order.total_amount)}</div>
                                <div class="text-xs text-muted">${escapeHtml(paymentLabel)}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline btn-sm" onclick="openOrderDetails(${order.id})">Lihat Detail</button>
                            ${actions}
                        </div>
                    </div>
                `;
            }).join('');

            lucide.createIcons();
        } catch (e) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Gagal Memuat Pesanan</h3>
                    <p>${escapeHtml(e.message)}</p>
                </div>
            `;
        }
    }

    function openOrderDetails(orderId) {
        const order = (window._buyerOrders || []).find(item => Number(item.id) === Number(orderId));
        if (!order) return;
        const product = order.product || {};
        const shippingAddress = order.shipping_address;
        const paymentStatusLabel = order.fulfillment_type === 'direct_deal' && order.payment_status === 'unpaid'
            ? 'Diatur langsung dengan penjual'
            : ({ unpaid: 'Belum dibayar', paid_escrow: 'Sudah dibayar · dana ditahan', released: 'Pembayaran selesai', refunded: 'Saldo dikembalikan' }[order.payment_status] || order.payment_status || '—');
        const orderStatusLabel = ({ pending: 'Menunggu Konfirmasi Penjual', processing: 'Sedang Disiapkan Penjual', shipped: 'Dalam Pengiriman Kurir', delivered: 'Barang Sudah Tiba', completed: 'Pesanan Selesai', cancelled: 'Pesanan Dibatalkan', disputed: 'Dalam Tinjauan Sengketa' }[order.order_status] || order.order_status || '—');
        const addressText = shippingAddress
            ? (typeof shippingAddress === 'string' ? shippingAddress : [shippingAddress.recipient_name, shippingAddress.full_address, shippingAddress.area_label].filter(Boolean).join(', '))
            : 'Pengambilan / pengaturan langsung dengan penjual';

        document.getElementById('orderDetailNumber').textContent = order.order_number;
        document.getElementById('orderDetailContent').innerHTML = `
            <div class="card p-4">
                <h4 class="font-bold">${escapeHtml(product.title || 'Produk')}</h4>
                <p class="text-sm text-muted mt-2" style="white-space: pre-line;">${escapeHtml(product.description || 'Deskripsi produk tidak tersedia.')}</p>
            </div>
            <div class="card p-4 text-sm flex flex-col gap-2">
                <div>Penjual: <strong>${escapeHtml(product.user?.name || '—')}</strong></div>
                <div>Status pesanan: <strong>${escapeHtml(orderStatusLabel)}</strong></div>
                <div>Status pembayaran: <strong>${escapeHtml(paymentStatusLabel)}</strong></div>
                <div>Metode: <strong>${order.fulfillment_type === 'expedition' ? 'Kurir ekspedisi' : 'Ambil langsung / atur dengan penjual'}</strong></div>
                <div>Alamat: <strong>${escapeHtml(addressText)}</strong></div>
                ${order.shipping_courier ? `<div>Kurir: <strong>${escapeHtml(order.shipping_courier)}</strong></div>` : ''}
                ${order.tracking_number ? `<div>Nomor resi: <strong>${escapeHtml(order.tracking_number)}</strong></div>` : ''}
                <hr class="divider" style="margin: 4px 0;">
                <div class="flex justify-between"><span>Harga barang</span><strong>${rupiah(order.item_amount)}</strong></div>
                <div class="flex justify-between"><span>Ongkir</span><strong>${rupiah(order.shipping_amount)}</strong></div>
                <div class="flex justify-between text-primary"><span>Total</span><strong>${rupiah(order.total_amount)}</strong></div>
            </div>
        `;

        document.getElementById('orderDetailActions').innerHTML = `
            ${order.fulfillment_type !== 'direct_deal' && order.order_status === 'pending' && order.payment_status === 'unpaid' ? `<button class="btn btn-primary flex-1" onclick="payBuyerOrder(${order.id})">Bayar dari Saldo</button>` : ''}
            ${!['completed', 'cancelled'].includes(order.order_status) ? `<button class="btn btn-outline flex-1" onclick="chatWithSeller(${order.id})">Chat Penjual</button>` : ''}
        `;
        document.getElementById('orderDetailModal').style.display = 'flex';
    }

    function closeOrderDetails() {
        document.getElementById('orderDetailModal').style.display = 'none';
    }

    async function payBuyerOrder(orderId) {
        if (!confirm('Bayar pesanan ini menggunakan saldo dompet?')) return;
        try {
            const res = await fetch(`${API}/orders/${orderId}/pay`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token()}` }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Pembayaran gagal.');
            closeOrderDetails();
            showToast(data.message || 'Pembayaran berhasil.', 'success');
            loadBuyerOrders();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    async function cancelBuyerOrder(id) {
        if (!confirm('Yakin ingin membatalkan pesanan ini? Saldo Anda akan dikembalikan.')) return;

        try {
            const res = await fetch(`${API}/orders/${id}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });

            if (!res.ok) throw new Error('Pesanan tidak dapat dibatalkan.');
            showToast('Pesanan berhasil dibatalkan.', 'success');
            loadBuyerOrders();
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    async function confirmBuyerOrder(id) {
        if (!confirm('Pastikan barang sudah Anda terima dengan baik. Dana akan diteruskan ke penjual.')) return;

        try {
            const res = await fetch(`${API}/orders/${id}/confirm`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Gagal mengonfirmasi pesanan.');
            showToast(data.order?.order_status === 'completed' ? 'Pesanan selesai! Dana diteruskan ke penjual.' : 'Penerimaan barang tercatat, tetapi dana belum dapat diteruskan. Hubungi admin jika ada laporan terbuka.', data.order?.order_status === 'completed' ? 'success' : 'error');
            loadBuyerOrders();
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    function openReportModal(orderId) {
        document.getElementById('reportOrderId').value = orderId;
        document.getElementById('reportExplanation').value = '';
        document.getElementById('reportModal').style.display = 'flex';
    }

    function closeReportModal() {
        document.getElementById('reportModal').style.display = 'none';
    }

    document.getElementById('reportOrderForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('reportOrderId').value;
        const reason = document.getElementById('reportReason').value;
        const details = document.getElementById('reportExplanation').value.trim();

        try {
            const res = await fetch(`${API}/orders/${id}/reports`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ reason, details })
            });

            if (!res.ok) throw new Error('Gagal mengirim laporan kendala.');
            closeReportModal();
            showToast('Laporan berhasil dikirim ke Admin untuk ditinjau.', 'success');
            loadBuyerOrders();
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    function chatWithSeller(orderId) {
        window.location.href = `/dashboard/chat?order_id=${orderId}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadBuyerOrders();
    });
</script>
@endpush
