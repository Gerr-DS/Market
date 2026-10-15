<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Isi saldo demo · Surplus</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f8f8f4;color:#172a24;font:16px system-ui,sans-serif}.card{width:min(440px,calc(100% - 32px));padding:32px;border:1px solid #e6eae5;border-radius:18px;background:#fff;text-align:center;box-shadow:0 16px 44px #14291b12}.icon{display:grid;place-items:center;width:64px;height:64px;margin:0 auto 18px;border-radius:50%;background:#eaf3ed;color:#1f694c;font-size:32px}.tag{display:inline-block;padding:6px 10px;border-radius:99px;background:#fff2e8;color:#9b5429;font-size:12px;font-weight:700}h1{margin:15px 0 8px;font-size:26px}p{line-height:1.6;color:#718078}.amount{margin:18px 0;font-size:30px;font-weight:800;color:#1f694c}a{display:inline-block;margin-top:12px;padding:12px 18px;border-radius:8px;background:#1f694c;color:#fff;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
    <main class="card">
        <div class="icon">✓</div>
        <span class="tag">PEMBAYARAN DEMO</span>
        <h1>{{ $alreadyPaid ? 'QR sudah dibayar' : 'Isi saldo berhasil' }}</h1>
        <div class="amount">Rp{{ number_format($amount, 0, ',', '.') }}</div>
        <p>Saldo virtual akun Surplus sudah diperbarui. Ini simulasi untuk tugas kuliah, bukan transaksi uang atau QRIS sungguhan.</p>
        <a href="/dashboard">Kembali ke Surplus</a>
    </main>
</body>
</html>
