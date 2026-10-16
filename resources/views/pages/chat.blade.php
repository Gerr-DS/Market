@extends('layouts.app')

@section('title', 'Pesan Chat — ReCircle')

@section('content')
<div class="page" style="padding-bottom: 20px;">
    <div class="container">
        <!-- Navigation Tabs for Dashboard -->
        <div class="tabs mb-4">
            <a href="/dashboard" class="tab">Ringkasan</a>
            <a href="/dashboard/products" class="tab">Produk Saya</a>
            <a href="/dashboard/sales" class="tab">Pesanan Masuk (Penjual)</a>
            <a href="/dashboard/orders" class="tab">Pesanan Saya (Pembeli)</a>
            <a href="/dashboard/wallet" class="tab">Dompet & Saldo</a>
            <a href="/dashboard/chat" class="tab active">Pesan Chat</a>
        </div>

        <!-- Chat Split Layout -->
        <div class="chat-layout">
            <!-- Sidebar: Conversation Threads -->
            <aside class="chat-sidebar">
                <div style="padding: 16px; border-bottom: 1px solid var(--clr-border);">
                    <h3 class="text-sm font-bold uppercase tracking-wider">Kotak Masuk Chat</h3>
                </div>
                <div id="chatConversationsList" style="flex: 1; overflow-y: auto;">
                    <div class="p-6 text-center text-xs text-muted">Memuat percakapan...</div>
                </div>
            </aside>

            <!-- Main Thread Window -->
            <main class="chat-main">
                <!-- Thread Header -->
                <div style="padding: 14px 20px; border-bottom: 1px solid var(--clr-border); display: flex; align-items: center; justify-content: space-between;">
                    <div class="flex items-center gap-3">
                        <div class="nav-avatar" id="activeChatAvatar">?</div>
                        <div>
                            <h3 id="activeChatUserName" class="font-bold text-sm">Pilih Percakapan</h3>
                            <p id="activeChatProductTitle" class="text-xs text-muted truncate" style="max-width: 350px;">Klik dari daftar di sebelah kiri untuk membuka pesan</p>
                        </div>
                    </div>
                </div>

                <!-- Messages Scroll Area -->
                <div id="chatMessagesArea" class="chat-messages">
                    <div class="empty-state">
                        <div class="empty-state-icon">💬</div>
                        <h3>Belum Ada Obrolan Dipilih</h3>
                        <p>Pilih salah satu kontak dari daftar percakapan di samping.</p>
                    </div>
                </div>

                <!-- Message Input Form -->
                <form id="chatSendForm" class="chat-input-area" style="display: none;">
                    <input type="text" id="chatMessageInput" class="chat-input" placeholder="Ketik pesan Anda di sini..." autocomplete="off" required>
                    <button type="submit" id="btnSendMessage" class="btn btn-primary btn-icon" title="Kirim Pesan">
                        <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                    </button>
                </form>
            </main>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let activeConversation = null;
    let chatPollTimer = null;
    const urlParams = new URLSearchParams(window.location.search);
    const initialOrderId = urlParams.get('order_id');
    const initialProductId = urlParams.get('product_id');

    async function loadConversations() {
        if (!token()) {
            openAuthModal('login');
            return;
        }

        const box = document.getElementById('chatConversationsList');
        try {
            const res = await fetch(`${API}/user/conversations`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });

            if (!res.ok) throw new Error('Gagal memuat daftar obrolan.');
            const list = await res.json();

            if (list.length === 0) {
                box.innerHTML = `
                    <div class="p-6 text-center text-xs text-muted">
                        Belum ada riwayat percakapan. Mulai obrolan langsung dari halaman produk atau pesanan.
                    </div>
                `;
                return;
            }

            box.innerHTML = list.map((c, i) => {
                const initial = (c.other_user_name || 'U').charAt(0).toUpperCase();
                return `
                    <div class="chat-item" data-index="${i}" onclick="selectConversation(${i}, this)">
                        <div class="chat-avatar">${initial}</div>
                        <div style="flex: 1; min-width: 0;">
                            <div class="flex justify-between items-center mb-1">
                                <strong class="text-xs truncate">${escapeHtml(c.other_user_name || 'Pengguna')}</strong>
                                <span class="badge badge-muted text-xs" style="font-size: 9px;">${escapeHtml(c.order_status || '')}</span>
                            </div>
                            <div class="text-xs text-muted truncate">${escapeHtml(c.product_title || 'Produk')}</div>
                            <div class="text-xs text-dim truncate mt-1">${escapeHtml(c.last_message || 'Obrolan baru')}</div>
                        </div>
                    </div>
                `;
            }).join('');

            window._conversations = list;

            // Auto-select if requested
            if (initialOrderId) {
                const target = list.find(c => String(c.order_id) === String(initialOrderId));
                if (target) {
                    selectConversation(list.indexOf(target));
                    return;
                }
            }

            if (list.length > 0 && !activeConversation) {
                selectConversation(0);
            }
        } catch (e) {
            box.innerHTML = `<div class="p-6 text-xs text-danger">${escapeHtml(e.message)}</div>`;
        }
    }

    function selectConversation(index, el) {
        const list = window._conversations || [];
        activeConversation = list[index];
        if (!activeConversation) return;

        document.querySelectorAll('.chat-item').forEach(item => item.classList.remove('active'));
        if (el) el.classList.add('active');

        document.getElementById('activeChatUserName').textContent = activeConversation.other_user_name || 'Pengguna';
        document.getElementById('activeChatProductTitle').textContent = activeConversation.product_title || 'Produk';
        document.getElementById('activeChatAvatar').textContent = (activeConversation.other_user_name || 'U').charAt(0).toUpperCase();

        document.getElementById('chatSendForm').style.display = 'flex';
        loadMessages();

        if (chatPollTimer) clearInterval(chatPollTimer);
        chatPollTimer = setInterval(loadMessages, 3000);
    }

    async function loadMessages() {
        if (!activeConversation) return;
        const area = document.getElementById('chatMessagesArea');

        try {
            const query = new URLSearchParams({
                product_id: activeConversation.product_id,
                other_user_id: activeConversation.other_user_id
            });

            const res = await fetch(`${API}/chats?${query.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                }
            });

            if (!res.ok) throw new Error('Gagal memuat pesan.');
            const data = await res.json();
            const messages = (data.data || []).reverse();

            if (messages.length === 0) {
                area.innerHTML = `
                    <div class="empty-state py-6">
                        <p class="text-sm text-muted">Belum ada pesan. Mulai obrolan dengan mengetik di bawah.</p>
                    </div>
                `;
                return;
            }

            // Current user ID check
            const myId = activeConversation.buyer_id === activeConversation.other_user_id 
                ? null 
                : activeConversation.buyer_id;

            area.innerHTML = messages.map(m => {
                const isMine = String(m.sender_id) !== String(activeConversation.other_user_id);
                return `
                    <div class="message-bubble ${isMine ? 'message-mine' : 'message-other'}">
                        <div>${escapeHtml(m.message)}</div>
                        <div class="text-xs ${isMine ? 'text-dim' : 'text-muted'} text-right mt-1" style="font-size: 10px;">
                            ${new Date(m.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                        </div>
                    </div>
                `;
            }).join('');

            area.scrollTop = area.scrollHeight;
        } catch (e) {
            console.error('Messages load error', e);
        }
    }

    document.getElementById('chatSendForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!activeConversation) return;

        const input = document.getElementById('chatMessageInput');
        const text = input.value.trim();
        if (!text) return;

        input.value = '';

        try {
            let endpoint = `${API}/products/${activeConversation.product_id}/chats`;
            if (activeConversation.order_id) {
                endpoint = `${API}/orders/${activeConversation.order_id}/chats`;
            }

            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token()}`
                },
                body: JSON.stringify({ message: text })
            });

            if (!res.ok) throw new Error('Gagal mengirim pesan.');
            loadMessages();
        } catch (err) {
            showToast(err.message, 'error');
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadConversations();
    });
</script>
@endpush
