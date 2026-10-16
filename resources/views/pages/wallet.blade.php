@extends('layouts.app')

@section('title', 'Dompet ReCircle — Saldo & Transaksi')

@section('content')
<div class="page">
    <div class="container" style="max-width: 900px;">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold">Dompet ReCircle</h1>
                <p class="text-sm text-muted">Kelola saldo akun, isi saldo via scan QR demo, dan pantau riwayat transaksi.</p>
            </div>
        </div>

        <!-- Navigation Tabs for Dashboard -->
        <div class="tabs">
            <a href="/dashboard" class="tab">Ringkasan</a>
            <a href="/dashboard/products" class="tab">Produk Saya</a>
            <a href="/dashboard/sales" class="tab">Pesanan Masuk (Penjual)</a>
            <a href="/dashboard/orders" class="tab">Pesanan Saya (Pembeli)</a>
            <a href="/dashboard/wallet" class="tab active">Dompet & Saldo</a>
            <a href="/dashboard/chat" class="tab">Pesan Chat</a>
        </div>

        <!-- Wallet Card Showcase -->
        <div class="wallet-card mb-8">
            <div style="position: relative; z-index: 1;">
                <div class="wallet-balance-label">TOTAL SALDO AKTIF ESCROW</div>
                <div class="wallet-balance-amount" id="walletDisplayAmount">Rp0</div>
                <p class="text-xs text-muted mt-2 max-w-md">
                    Saldo ini dapat digunakan untuk transaksi pembelian langsung, pembayaran ongkir, atau ditarik ke rekening bank demo.
                </p>

                <div class="flex gap-3 mt-6 flex-wrap">
                    <button type="button" class="btn btn-primary btn-lg" onclick="openTopupModal()">
                        <i data-lucide="qr-code" style="width: 18px; height: 18px;"></i>
                        <span>Isi Saldo (Scan QR Demo)</span>
                    </button>
                    <button type="button" class="btn btn-outline btn-lg" onclick="openWithdrawModal()">
                        <i data-lucide="arrow-up-right" style="width: 18px; height: 18px;"></i>
                        <span>Tarik Saldo</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Transactions History Section -->
        <div class="card p-6">
            <h3 class="text-base font-bold uppercase tracking-wider mb-4 flex items-center justify-between">
                <span>Riwayat Mutasi Saldo</span>
                <span class="text-xs text-muted font-normal">Real-time ledger</span>
            </h3>

            <div id="transactionsContainer" class="flex flex-col gap-2">
                <div class="card p-6 text-center text-xs text-muted">Memuat riwayat transaksi...</div>
            </div>
        </div>
    </div>
</div>

<!-- Demo Top-Up QR Modal -->
<div id="walletTopupModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 480px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Isi Saldo Demo (QR Scan)</h3>
                <p class="text-xs text-muted mt-1">Uji coba simulasi pembayaran cepat berbasis QR.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeTopupModal()">✕</button>
        </div>

        <!-- Input Form -->
        <form id="topupFormArea" class="flex flex-col gap-4">
            <div class="form-group">
                <label class="form-label">Pilih Nominal Top-Up</label>
                <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 8px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="setTopupAmount(25000)">Rp25.000</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="setTopupAmount(50000)">Rp50.000</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="setTopupAmount(100000)">Rp100.000</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="setTopupAmount(250000)">Rp250.000</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="setTopupAmount(500000)">Rp500.000</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="setTopupAmount(1000000)">Rp1.000.000</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Atau Ketik Nominal Sendiri (Rp)</label>
                <input type="number" id="inputTopupAmount" class="form-input" min="1000" max="5000000" step="1000" value="50000" required>
            </div>

            <button type="submit" id="btnGenerateQr" class="btn btn-primary btn-lg w-full mt-2">
                <i data-lucide="qr-code" style="width: 16px; height: 16px;"></i>
                <span>Generate QR Pembayaran</span>
            </button>
        </form>

        <!-- QR Display Area (Shown after submit) -->
        <div id="topupQrArea" class="flex flex-col items-center gap-4 py-4" style="display: none;">
            <div id="topupSvgBox" style="background: white; padding: 16px; border-radius: var(--radius-lg); display: flex; justify-content: center;"></div>
            
            <div class="text-center">
                <div id="topupStatusMsg" class="font-bold text-sm text-primary">Menunggu scan kamera...</div>
                <p class="text-xs text-muted mt-1" style="max-width: 320px;">
                    Buka tautan ini di browser/ponsel untuk langsung menambahkan saldo demo secara instan.
                </p>
                <a id="directScanLink" href="#" target="_blank" class="btn btn-outline btn-sm mt-3">
                    Buka Tautan Simulasi Bayar →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="walletWithdrawModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 480px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Tarik Saldo ke Rekening Bank</h3>
                <p class="text-xs text-muted mt-1">Simulasi pencairan dana penjualan ke rekening terdaftar.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeWithdrawModal()">✕</button>
        </div>

        <form id="withdrawForm" class="flex flex-col gap-4">
            <div class="form-group">
                <label class="form-label">Nominal Penarikan (Rp)</label>
                <input type="number" name="amount" class="form-input" min="10000" step="1000" placeholder="Minimal Rp10.000" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Bank</label>
                <input type="text" name="bank_name" class="form-input" placeholder="BCA / Mandiri / BNI / BRI / GoPay / OVO" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor Rekening / E-Wallet</label>
                <input type="text" name="account_number" class="form-input" placeholder="Contoh: 1234567890" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Pemilik Rekening</label>
                <input type="text" name="account_holder_name" class="form-input" placeholder="Nama sesuai buku tabungan" required>
            </div>

            <div id="withdrawErrorMsg" class="text-xs text-danger" style="display: none;"></div>

            <button type="submit" id="btnSubmitWithdraw" class="btn btn-primary btn-lg w-full mt-2">
                Kirim Permintaan Penarikan
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let topupPollTimer = null;

    async function loadWalletData() {
        if (!token()) {
            openAuthModal('login');
            return;
        }

        const headers = {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token()}`
        };

        try {
            const [profileRes, txRes] = await Promise.all([
                fetch(`${API}/user/profile`, { headers }),
                fetch(`${API}/user/wallet/transactions`, { headers })
            ]);

            if (profileRes.ok) {
                const profile = await profileRes.json();
                document.getElementById('walletDisplayAmount').textContent = rupiah(profile.wallet_balance);
            }

            if (txRes.ok) {
                const txData = await txRes.json();
                const list = txData.data || [];
                renderTransactions(list);
            }
        } catch (e) {
            console.error('Wallet fetch error', e);
        }
    }

    function renderTransactions(list) {
        const container = document.getElementById('transactionsContainer');
        if (list.length === 0) {
            container.innerHTML = `
                <div class="empty-state py-6">
                    <div class="empty-state-icon">💳</div>
                    <h3>Belum Ada Transaksi Saldo</h3>
                    <p>Transaksi saldo, penjualan, dan pembelian akan tercatat di sini.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = list.map(t => {
            const isDebit = t.type === 'debit';
            const sign = isDebit ? '−' : '+';
            const colorClass = isDebit ? 'text-danger' : 'text-primary';
            const iconName = isDebit ? 'arrow-up-right' : 'arrow-down-left';

            return `
                <div class="card p-4 flex items-center justify-between" style="background: var(--clr-surface);">
                    <div class="flex items-center gap-3">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: ${isDebit ? 'rgba(255,71,87,0.15)' : 'rgba(0,200,150,0.15)'}; display: flex; align-items: center; justify-content: center; color: ${isDebit ? 'var(--clr-danger)' : 'var(--clr-primary)'};">
                            <i data-lucide="${iconName}" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm">${escapeHtml(t.description || 'Transaksi Dompet')}</div>
                            <div class="text-xs text-muted">${new Date(t.created_at).toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                    <div class="font-bold text-base ${colorClass}">
                        ${sign}${rupiah(t.amount)}
                    </div>
                </div>
            `;
        }).join('');

        lucide.createIcons();
    }

    function openTopupModal() {
        document.getElementById('topupFormArea').reset();
        document.getElementById('topupFormArea').style.display = 'flex';
        document.getElementById('topupQrArea').style.display = 'none';
        document.getElementById('walletTopupModal').style.display = 'flex';
        if (topupPollTimer) clearInterval(topupPollTimer);
    }

    function closeTopupModal() {
        document.getElementById('walletTopupModal').style.display = 'none';
        if (topupPollTimer) clearInterval(topupPollTimer);
    }

    function setTopupAmount(amt) {
        document.getElementById('inputTopupAmount').value = amt;
    }

    document.getElementById('topupFormArea')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const amt = Number(document.getElementById('inputTopupAmount').value);
        const btn = document.getElementById('btnGenerateQr');
        btn.disabled = true;
        btn.textContent = 'Membuat QR...';

        try {
            const res = await fetch(`${API}/user/wallet/demo-topups`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ amount: amt })
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal membuat QR.');

            const scanUrl = window.location.origin + data.scan_path;

            // Generate SVG QR
            if (window.SurplusQRCode) {
                const qr = new window.SurplusQRCode(-1, window.SurplusQRErrorCorrectLevel.M);
                qr.addData(scanUrl);
                qr.make();
                const count = qr.getModuleCount();
                const quiet = 4;
                const size = count + quiet * 2;
                let cells = '';
                for (let r = 0; r < count; r++) {
                    for (let c = 0; c < count; c++) {
                        if (qr.modules[r][c]) {
                            cells += `<rect x="${c + quiet}" y="${r + quiet}" width="1" height="1"/>`;
                        }
                    }
                }
                document.getElementById('topupSvgBox').innerHTML = `
                    <svg viewBox="0 0 ${size} ${size}" style="width: 240px; height: 240px; shape-rendering: crispEdges;">
                        <rect width="${size}" height="${size}" fill="white"/>
                        <g fill="#07110e">${cells}</g>
                    </svg>
                `;
            }

            document.getElementById('topupStatusMsg').textContent = `Menunggu Scan · ${rupiah(data.amount)}`;
            document.getElementById('directScanLink').href = data.scan_path;

            document.getElementById('topupFormArea').style.display = 'none';
            document.getElementById('topupQrArea').style.display = 'flex';

            // Poll status
            if (topupPollTimer) clearInterval(topupPollTimer);
            topupPollTimer = setInterval(async () => {
                try {
                    const statusRes = await fetch(`${API}/user/wallet/demo-topups/${data.id}`, {
                        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token()}` }
                    });
                    const statusData = await statusRes.json();
                    if (statusData.status === 'paid') {
                        clearInterval(topupPollTimer);
                        showToast(`Top-up sebesar ${rupiah(statusData.amount)} berhasil!`, 'success');
                        closeTopupModal();
                        loadWalletData();
                    }
                } catch (_) {}
            }, 2000);
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Generate QR Pembayaran';
        }
    });

    function openWithdrawModal() {
        document.getElementById('withdrawForm').reset();
        document.getElementById('withdrawErrorMsg').style.display = 'none';
        document.getElementById('walletWithdrawModal').style.display = 'flex';
    }

    function closeWithdrawModal() {
        document.getElementById('walletWithdrawModal').style.display = 'none';
    }

    document.getElementById('withdrawForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('withdrawErrorMsg');
        const btn = document.getElementById('btnSubmitWithdraw');
        err.style.display = 'none';

        const formData = new FormData(e.currentTarget);
        const payload = Object.fromEntries(formData);
        payload.amount = Number(payload.amount);

        btn.disabled = true;
        btn.textContent = 'Memproses...';

        try {
            const res = await fetch(`${API}/user/wallet/withdrawals`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || data.errors?.amount?.[0] || 'Penarikan gagal.');

            closeWithdrawModal();
            showToast('Permintaan penarikan demo berhasil dikirim.', 'success');
            loadWalletData();
        } catch (error) {
            err.textContent = error.message;
            err.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Kirim Permintaan Penarikan';
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadWalletData();
    });
</script>
@endpush
