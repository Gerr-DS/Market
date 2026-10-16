@extends('layouts.app')

@section('title', 'Panel Admin Moderasi — ReCircle')

@section('content')
<div class="page">
    <div class="container">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
            <div>
                <span class="badge badge-primary mb-2">Panel Moderasi & Pengawasan</span>
                <h1 class="text-2xl font-bold">Pusat Resolusi Admin</h1>
                <p class="text-sm text-muted">Tinjau sengketa pesanan pembeli dan setujui penarikan saldo escrow.</p>
            </div>
            <button type="button" class="btn btn-outline btn-sm" onclick="loadAdminData()">
                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                <span>Muat Ulang Data</span>
            </button>
        </div>

        <div class="flex flex-col gap-8">
            <!-- Reports / Dispute Section -->
            <div class="card p-6">
                <h3 class="text-base font-bold uppercase tracking-wider mb-4 flex items-center justify-between">
                    <span>Laporan Sengketa Pesanan</span>
                    <span id="reportsCountBadge" class="badge badge-warning">0 Laporan</span>
                </h3>
                <div id="adminReportsContainer" class="flex flex-col gap-3">
                    <div class="p-6 text-center text-xs text-muted">Memuat laporan...</div>
                </div>
            </div>

            <!-- Withdrawals Section -->
            <div class="card p-6">
                <h3 class="text-base font-bold uppercase tracking-wider mb-4 flex items-center justify-between">
                    <span>Permintaan Pencairan Saldo Bank</span>
                    <span id="withdrawalsCountBadge" class="badge badge-info">0 Pengajuan</span>
                </h3>
                <div id="adminWithdrawalsContainer" class="flex flex-col gap-3">
                    <div class="p-6 text-center text-xs text-muted">Memuat data penarikan...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadAdminData() {
        if (!token()) {
            openAuthModal('login');
            return;
        }

        const headers = {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token()}`
        };

        try {
            const [reportsRes, withdrawalsRes] = await Promise.all([
                fetch(`${API}/admin/reports`, { headers }),
                fetch(`${API}/admin/withdrawals`, { headers })
            ]);

            if (reportsRes.ok) {
                const repData = await reportsRes.json();
                renderReports(repData.data || repData);
            }

            if (withdrawalsRes.ok) {
                const withData = await withdrawalsRes.json();
                renderWithdrawals(withData.data || withData);
            }
        } catch (e) {
            console.error('Admin load error', e);
        }
    }

    function renderReports(reports) {
        const box = document.getElementById('adminReportsContainer');
        document.getElementById('reportsCountBadge').textContent = `${reports.length} Laporan`;

        if (reports.length === 0) {
            box.innerHTML = '<div class="empty-state py-6"><p class="text-xs text-muted">Tidak ada laporan sengketa aktif saat ini.</p></div>';
            return;
        }

        box.innerHTML = reports.map(r => {
            const isResolved = r.status === 'resolved';
            return `
                <div class="card p-4 flex items-center justify-between flex-wrap gap-4" style="background: var(--clr-surface);">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="badge ${isResolved ? 'badge-success' : 'badge-danger'}">${isResolved ? 'Selesai' : 'Perlu Tindakan'}</span>
                            <span class="text-xs text-muted">Pesanan #${escapeHtml(r.order?.order_number || r.order_id)}</span>
                        </div>
                        <h4 class="font-bold text-sm">${escapeHtml(r.reason || 'Laporan Masalah')}</h4>
                        <p class="text-xs text-dim mt-1 max-w-lg">${escapeHtml(r.details || '')}</p>
                    </div>

                    ${!isResolved ? `
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="decideReport(${r.id}, 'refund_buyer')">
                                Kembalikan Dana (Refund)
                            </button>
                            <button type="button" class="btn btn-outline btn-sm text-danger" onclick="decideReport(${r.id}, 'reject_report')">
                                Tolak Laporan
                            </button>
                        </div>
                    ` : '<span class="text-xs text-muted">Laporan telah diselesaikan</span>'}
                </div>
            `;
        }).join('');
    }

    function renderWithdrawals(withdrawals) {
        const box = document.getElementById('adminWithdrawalsContainer');
        document.getElementById('withdrawalsCountBadge').textContent = `${withdrawals.length} Pengajuan`;

        if (withdrawals.length === 0) {
            box.innerHTML = '<div class="empty-state py-6"><p class="text-xs text-muted">Belum ada pengajuan pencairan dana.</p></div>';
            return;
        }

        box.innerHTML = withdrawals.map(w => {
            const isPending = w.status === 'pending';
            const statusBadges = {
                pending: '<span class="badge badge-warning">Menunggu Keputusan</span>',
                approved: '<span class="badge badge-success">Disetujui</span>',
                rejected: '<span class="badge badge-danger">Ditolak</span>'
            };

            return `
                <div class="card p-4 flex items-center justify-between flex-wrap gap-4" style="background: var(--clr-surface);">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            ${statusBadges[w.status] || ''}
                            <span class="text-xs font-bold text-primary">${rupiah(w.amount)}</span>
                        </div>
                        <h4 class="font-bold text-sm">${escapeHtml(w.bank_name)} · ${escapeHtml(w.account_number)}</h4>
                        <p class="text-xs text-muted mt-1">A/N: ${escapeHtml(w.account_holder_name)} (User: ${escapeHtml(w.user?.name || '')})</p>
                    </div>

                    ${isPending ? `
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="decideWithdrawal(${w.id}, 'approve')">
                                Setujui Pencairan
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="decideWithdrawal(${w.id}, 'reject')">
                                Tolak
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }

    async function decideReport(id, action) {
        try {
            const res = await fetch(`${API}/admin/reports/${id}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ action })
            });

            if (!res.ok) throw new Error('Gagal memproses keputusan laporan.');
            showToast('Keputusan berhasil disimpan.', 'success');
            loadAdminData();
        } catch (e) {
            showToast(e.message, 'error');
        }
    }

    async function decideWithdrawal(id, action) {
        try {
            const res = await fetch(`${API}/admin/withdrawals/${id}/decision`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ action })
            });

            if (!res.ok) throw new Error('Gagal memproses penarikan.');
            showToast(`Pengajuan berhasil di-${action === 'approve' ? 'setujui' : 'tolak'}.`, 'success');
            loadAdminData();
        } catch (e) {
            showToast(e.message, 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadAdminData();
    });
</script>
@endpush
