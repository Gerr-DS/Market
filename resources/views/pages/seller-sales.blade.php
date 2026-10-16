@extends('layouts.app')

@section('title', 'Pesanan Masuk (Penjual) — ReCircle')

@section('content')
<div class="page">
    <div class="container">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold">Pesanan Masuk</h1>
                <p class="text-sm text-muted">Kelola pesanan dari pembeli untuk produk yang Anda tawarkan.</p>
            </div>
        </div>

        <!-- Navigation Tabs for Dashboard -->
        <div class="tabs">
            <a href="/dashboard" class="tab">Ringkasan</a>
            <a href="/dashboard/products" class="tab">Produk Saya</a>
            <a href="/dashboard/sales" class="tab active">Pesanan Masuk (Penjual)</a>
            <a href="/dashboard/orders" class="tab">Pesanan Saya (Pembeli)</a>
            <a href="/dashboard/wallet" class="tab">Dompet & Saldo</a>
            <a href="/dashboard/chat" class="tab">Pesan Chat</a>
        </div>

        <!-- Sales Order List -->
        <div id="sellerSalesContainer" class="flex flex-col gap-4">
            <div class="card p-8 flex justify-center items-center">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadSellerSales() {
        if (!token()) {
            openAuthModal('login');
            return;
        }

        const container = document.getElementById('sellerSalesContainer');
        try {
            const res = await fetch(`${API}/user/sales`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });

            if (!res.ok) throw new Error('Gagal mengambil daftar pesanan masuk.');
            const json = await res.json();
            const orders = json.data || [];

            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📥</div>
                        <h3>Belum Ada Pesanan Masuk</h3>
                        <p>Ketika ada pembeli yang memesan produk Anda, pesanan akan muncul di sini.</p>
                    </div>
                `;
                return;
            }

            const statusLabels = {
                pending: '<span class="badge badge-warning">Menunggu Diproses</span>',
                processing: '<span class="badge badge-info">Siap Diserahkan / Disiapkan</span>',
                shipped: '<span class="badge badge-info">Dalam Perjalanan Kurir</span>',
                delivered: '<span class="badge badge-primary">Terkirim / Sampai</span>',
                completed: '<span class="badge badge-success">Selesai</span>',
                cancelled: '<span class="badge badge-danger">Dibatalkan / Ditolak</span>',
                disputed: '<span class="badge badge-danger">Dalam Tinjauan Masalah</span>'
            };
            const paymentLabels = {
                unpaid: '<span class="badge badge-warning">Belum dibayar</span>',
                paid_escrow: '<span class="badge badge-success">Sudah dibayar</span>',
                released: '<span class="badge badge-success">Pembayaran selesai</span>',
                refunded: '<span class="badge badge-muted">Saldo dikembalikan</span>'
            };

            container.innerHTML = orders.map(order => {
                const isDirect = order.fulfillment_type === 'direct_deal';
                const p = order.product || {};
                const img = p.images?.[0]?.image_path;
                const paymentLabel = isDirect && order.payment_status === 'unpaid'
                    ? '<span class="badge badge-muted">Diatur langsung dengan penjual</span>'
                    : (paymentLabels[order.payment_status] || '');

                let actionButtons = '';
                const isClosed = ['completed', 'cancelled'].includes(order.order_status);

                if (!isClosed) {
                    actionButtons += `
                        <button type="button" class="btn btn-outline btn-sm" onclick="chatWithBuyer(${order.id})">
                            <i data-lucide="message-square" style="width: 14px; height: 14px;"></i>
                            <span>Chat Pembeli</span>
                        </button>
                    `;
                }

                if (order.order_status === 'pending' && ['unpaid', 'paid_escrow'].includes(order.payment_status)) {
                    actionButtons += `
                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectSellerOrder(${order.id})">
                            <i data-lucide="x-circle" style="width: 14px; height: 14px;"></i>
                            <span>Tolak Pesanan</span>
                        </button>
                    `;
                }

                if (isDirect) {
                    if (order.order_status === 'pending' && ['unpaid', 'paid_escrow'].includes(order.payment_status)) {
                        actionButtons += `
                            <button type="button" class="btn btn-primary btn-sm" onclick="updateDirectHandoff(${order.id}, 'start')">
                                <i data-lucide="handshake" style="width: 14px; height: 14px;"></i>
                                <span>Mulai Serah Terima (COD)</span>
                            </button>
                        `;
                    } else if (order.order_status === 'processing') {
                        actionButtons += `
                            <button type="button" class="btn btn-primary btn-sm" onclick="updateDirectHandoff(${order.id}, 'complete')">
                                <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i>
                                <span>Tandai Sudah Diterima Pembeli</span>
                            </button>
                        `;
                    }
                } else {
                    // Expedition
                    if (['pending', 'processing'].includes(order.order_status) && order.payment_status === 'paid_escrow') {
                        actionButtons += `
                            <button type="button" class="btn btn-primary btn-sm" onclick="shipExpeditionOrder(${order.id})">
                                <i data-lucide="truck" style="width: 14px; height: 14px;"></i>
                                <span>Kirim via Ekspedisi Kurir</span>
                            </button>
                        `;
                    }
                }

                return `
                    <div class="card p-5 flex items-center justify-between flex-wrap gap-4" style="background: var(--clr-card);">
                        <div class="flex items-center gap-4">
                            <img src="${imageUrl(img)}" class="upload-thumb" style="width: 72px; height: 72px; border-radius: var(--radius-md);">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold text-muted">${escapeHtml(order.order_number)}</span>
                                    ${statusLabels[order.order_status] || ''}
                                    ${paymentLabel}
                                    <span class="badge badge-muted">${isDirect ? 'Ambil Langsung' : 'Kurir Ekspedisi'}</span>
                                </div>
                                <h3 class="font-bold text-base mb-1">${escapeHtml(p.title || 'Produk')}</h3>
                                <div class="text-xs text-muted mb-1">
                                    Pembeli: <strong>${escapeHtml(order.buyer?.name || '—')}</strong> (${escapeHtml(order.buyer?.email || '')})
                                </div>
                                <div class="text-primary font-bold text-base">${rupiah(order.total_amount)}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            ${actionButtons}
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

    async function updateDirectHandoff(id, action) {
        try {
            const res = await fetch(`${API}/orders/${id}/handoff`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ action })
            });

            if (!res.ok) throw new Error('Gagal memperbarui status serah terima.');

            showToast(action === 'start' ? 'Serah terima dimulai! Hubungi pembeli via chat.' : 'Pesanan ditandai selesai diserahkan.', 'success');
            loadSellerSales();
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    async function shipExpeditionOrder(id) {
        try {
            const res = await fetch(`${API}/orders/${id}/ship`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({})
            });

            if (!res.ok) throw new Error('Gagal menandai paket dikirim.');

            showToast('Paket ditandai dikirim ke kurir!', 'success');
            loadSellerSales();
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    async function rejectSellerOrder(id) {
        if (!confirm('Tolak pesanan ini? Stok produk akan dikembalikan dan pembeli tidak dapat melanjutkan pesanan.')) return;

        try {
            const res = await fetch(`${API}/orders/${id}/reject`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Pesanan tidak dapat ditolak.');

            showToast(data.message || 'Pesanan berhasil ditolak.', 'success');
            loadSellerSales();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function chatWithBuyer(orderId) {
        window.location.href = `/dashboard/chat?order_id=${orderId}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadSellerSales();
    });
</script>
@endpush
