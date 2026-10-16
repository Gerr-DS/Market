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

            container.innerHTML = orders.map(order => {
                const p = order.product || {};
                const img = p.images?.[0]?.image_path;
                const isDirect = order.fulfillment_type === 'direct_deal';
                const isClosed = ['completed', 'cancelled'].includes(order.order_status);

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

                if (['shipped', 'delivered'].includes(order.order_status)) {
                    actions += `
                        <button type="button" class="btn btn-primary btn-sm" onclick="confirmBuyerOrder(${order.id})">
                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                            <span>Konfirmasi Selesai / Diterima</span>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="openReportModal(${order.id})">
                            Laporkan Masalah
                        </button>
                    `;
                }

                return `
                    <div class="card p-5 flex items-center justify-between flex-wrap gap-4" style="background: var(--clr-card);">
                        <div class="flex items-center gap-4">
                            <img src="${imageUrl(img)}" class="upload-thumb" style="width: 72px; height: 72px; border-radius: var(--radius-md);">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold text-muted">${escapeHtml(order.order_number)}</span>
                                    ${statusLabels[order.order_status] || ''}
                                    <span class="badge badge-muted">${isDirect ? 'Ambil Langsung (COD)' : 'Ekspedisi Kurir'}</span>
                                </div>
                                <h3 class="font-bold text-base mb-1">${escapeHtml(p.title || 'Barang Pesanan')}</h3>
                                <div class="text-xs text-muted mb-1">
                                    Penjual: <strong>${escapeHtml(p.user?.name || 'Komunitas ReCircle')}</strong>
                                </div>
                                <div class="text-primary font-bold text-base">${rupiah(order.total_amount)}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
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

            if (!res.ok) throw new Error('Gagal mengonfirmasi pesanan.');
            showToast('Pesanan selesai! Terima kasih telah berbelanja sirkular.', 'success');
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
