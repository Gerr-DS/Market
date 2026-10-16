@extends('layouts.app')

@section('title', 'Masuk / Daftar Akun — ReCircle')

@section('content')
<div class="page flex items-center justify-center" style="min-height: calc(100vh - 120px);">
    <div class="card p-8 w-full" style="max-width: 460px; margin: auto;">
        <div class="text-center mb-6">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--clr-primary); display: inline-flex; align-items: center; justify-content: center; color: #07110e; margin-bottom: 12px;">
                <i data-lucide="recycle" style="width: 26px; height: 26px;"></i>
            </div>
            <h1 id="pageAuthTitle" class="text-2xl font-bold">Selamat Datang Kembali</h1>
            <p id="pageAuthSub" class="text-xs text-muted mt-1">Masuk untuk melanjutkan aksi sirkularmu di ReCircle.</p>
        </div>

        <div class="tabs justify-center mb-6">
            <button type="button" id="tabLogin" class="tab active" onclick="switchAuthTab('login')">Masuk</button>
            <button type="button" id="tabRegister" class="tab" onclick="switchAuthTab('register')">Daftar Akun Baru</button>
        </div>

        <form id="standaloneAuthForm" class="flex flex-col gap-4">
            <div id="pageNameGroup" class="form-group" style="display: none;">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" id="pageName" class="form-input" placeholder="Nama lengkap Anda">
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Email</label>
                <input type="email" id="pageEmail" class="form-input" required placeholder="nama@email.com">
            </div>

            <div id="pagePhoneGroup" class="form-group" style="display: none;">
                <label class="form-label">Nomor WhatsApp</label>
                <input type="tel" id="pagePhone" class="form-input" placeholder="08xxxxxxxxxx">
            </div>

            <div class="form-group">
                <label class="form-label">Kata Sandi</label>
                <input type="password" id="pagePassword" class="form-input" minlength="8" required placeholder="Minimal 8 karakter">
            </div>

            <div id="pageAuthError" class="text-xs text-danger" style="display: none;"></div>

            <button type="submit" id="btnPageAuthSubmit" class="btn btn-primary btn-lg w-full mt-2">
                Masuk Sekarang
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentAuthMode = window.location.pathname.includes('register') ? 'register' : 'login';

    function switchAuthTab(mode) {
        currentAuthMode = mode;
        const nameGroup = document.getElementById('pageNameGroup');
        const phoneGroup = document.getElementById('pagePhoneGroup');
        const title = document.getElementById('pageAuthTitle');
        const sub = document.getElementById('pageAuthSub');
        const submitBtn = document.getElementById('btnPageAuthSubmit');
        const err = document.getElementById('pageAuthError');
        err.style.display = 'none';

        if (mode === 'register') {
            nameGroup.style.display = 'flex';
            phoneGroup.style.display = 'flex';
            title.textContent = 'Daftar Akun Baru';
            sub.textContent = 'Bergabunglah bersama ribuan penggerak ekonomi sirkular.';
            submitBtn.textContent = 'Buat Akun ReCircle';
            document.getElementById('tabRegister').classList.add('active');
            document.getElementById('tabLogin').classList.remove('active');
        } else {
            nameGroup.style.display = 'none';
            phoneGroup.style.display = 'none';
            title.textContent = 'Selamat Datang Kembali';
            sub.textContent = 'Masuk untuk melanjutkan aksi sirkularmu di ReCircle.';
            submitBtn.textContent = 'Masuk Sekarang';
            document.getElementById('tabLogin').classList.add('active');
            document.getElementById('tabRegister').classList.remove('active');
        }
    }

    document.getElementById('standaloneAuthForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const err = document.getElementById('pageAuthError');
        const btn = document.getElementById('btnPageAuthSubmit');
        err.style.display = 'none';

        const isReg = (currentAuthMode === 'register');
        const payload = {
            email: document.getElementById('pageEmail').value.trim(),
            password: document.getElementById('pagePassword').value
        };

        if (isReg) {
            payload.name = document.getElementById('pageName').value.trim();
            payload.phone = document.getElementById('pagePhone').value.trim();
        }

        btn.disabled = true;
        btn.textContent = 'Memproses...';

        try {
            const endpoint = isReg ? `${API}/auth/register` : `${API}/auth/login`;
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Otentikasi gagal.');

            setToken(data.token);
            showToast(isReg ? 'Akun berhasil dibuat! Selamat datang.' : 'Berhasil masuk.', 'success');
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 800);
        } catch (error) {
            err.textContent = error.message;
            err.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = isReg ? 'Buat Akun ReCircle' : 'Masuk Sekarang';
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        switchAuthTab(currentAuthMode);
    });
</script>
@endpush
