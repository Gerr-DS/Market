<footer style="background: var(--clr-surface); border-top: 1px solid var(--clr-border); padding: 50px 0 24px; margin-top: 60px;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 36px; margin-bottom: 40px;">
            <!-- Brand Column -->
            <div>
                <a href="/" class="navbar-logo" style="margin-bottom: 14px;">
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: var(--clr-primary); display: flex; align-items: center; justify-content: center; color: #07110e;">
                        <i data-lucide="recycle" style="width: 16px; height: 16px;"></i>
                    </div>
                    <span>re<b>circle</b></span>
                </a>
                <p class="text-sm text-muted" style="line-height: 1.7; max-width: 280px;">
                    Platform sirkular cerdas untuk menyelamatkan makanan surplus dan memperpanjang usia pakai barang preloved pilihan.
                </p>
                <div class="flex items-center gap-3 mt-4 text-primary">
                    <span class="badge badge-primary">AI Powered Verification</span>
                    <span class="badge badge-success">Eco Impact</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-sm font-bold text-muted uppercase tracking-wider mb-4">Navigasi Pasar</h4>
                <ul class="flex flex-col gap-2 text-sm text-muted">
                    <li><a href="/products" class="hover:text-primary transition">Semua Produk</a></li>
                    <li><a href="/products?type=food" class="hover:text-primary transition">Makanan Surplus Segar</a></li>
                    <li><a href="/products?type=goods" class="hover:text-primary transition">Barang Preloved Berkualitas</a></li>
                    <li><a href="/sell" class="hover:text-primary transition">Jual Barang dengan AI</a></li>
                </ul>
            </div>

            <!-- User Space -->
            <div>
                <h4 class="text-sm font-bold text-muted uppercase tracking-wider mb-4">Ruang Akun</h4>
                <ul class="flex flex-col gap-2 text-sm text-muted">
                    <li><a href="/dashboard" class="hover:text-primary transition">Dashboard Profil</a></li>
                    <li><a href="/dashboard/products" class="hover:text-primary transition">Etalase Saya</a></li>
                    <li><a href="/dashboard/orders" class="hover:text-primary transition">Riwayat Pembelian</a></li>
                    <li><a href="/dashboard/wallet" class="hover:text-primary transition">Dompet & Saldo Demo</a></li>
                </ul>
            </div>

            <!-- Eco Mission -->
            <div>
                <h4 class="text-sm font-bold text-muted uppercase tracking-wider mb-4">Misi Berkelanjutan</h4>
                <p class="text-xs text-muted" style="line-height: 1.8;">
                    Setiap transaksi di ReCircle berkontribusi langsung pada pengurangan limbah organik TPA dan penurunan emisi karbon. Mari bergerak bersama untuk masa depan bumi.
                </p>
                <div class="mt-4 p-3 card" style="background: rgba(0,200,150,0.06); border-color: rgba(0,200,150,0.2);">
                    <div class="text-xs font-semibold text-primary flex items-center gap-2">
                        <i data-lucide="leaf" style="width: 14px; height: 14px;"></i>
                        <span>Zero Waste · Circular Economy</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid var(--clr-border); padding-top: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;" class="text-xs text-dim">
            <div>
                © {{ date('Y') }} <strong>ReCircle / Surplus Market</strong>. Seluruh hak cipta dilindungi.
            </div>
            <div class="flex gap-4">
                <span>Inisiatif Berkelanjutan Mahasiswa</span>
                <span>•</span>
                <span>Didukung oleh Gemini AI</span>
            </div>
        </div>
    </div>
</footer>
