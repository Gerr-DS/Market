<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function mine(Request $request)
    {
        return Order::with(['product.images', 'product.user:id,name'])
            ->where('buyer_id', $request->user()->id)->latest()->paginate(20);
    }

    public function incoming(Request $request)
    {
        return Order::with(['product.images', 'buyer:id,name'])
            ->where(fn ($query) => $query->where('payment_status', '!=', 'unpaid')->orWhere('fulfillment_type', 'direct_deal'))
            ->whereHas('product', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()->paginate(20);
    }

    public function cancel(Request $request, Order $order)
    {
        $order = DB::transaction(function () use ($request, $order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->buyer_id === $request->user()->id, 403);
            abort_unless(in_array($locked->order_status, ['pending', 'processing'], true), 409, 'Pesanan hanya dapat dibatalkan sebelum dikirim.');
            $this->refundIfPaid($locked);
            $locked->update(['order_status' => 'cancelled']);
            $product = Product::withTrashed()->whereKey($locked->product_id)->lockForUpdate()->first();
            if ($product) {
                $product->increment('stock');
            }
            return $locked;
        });

        return $order;
    }

    public function rejectBySeller(Request $request, Order $order)
    {
        $order = DB::transaction(function () use ($request, $order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $product = Product::withTrashed()->whereKey($locked->product_id)->lockForUpdate()->first();
            abort_unless($product && $product->user_id === $request->user()->id, 403);
            abort_unless($locked->order_status === 'pending' && in_array($locked->payment_status, ['unpaid', 'paid_escrow'], true), 409, 'Pesanan tidak dapat ditolak karena sudah diproses atau diselesaikan.');

            $this->refundIfPaid($locked);
            $locked->update(['order_status' => 'cancelled']);
            $product->increment('stock');

            return $locked;
        });

        return response()->json(['order' => $order, 'message' => 'Pesanan ditolak. Saldo pembeli dikembalikan jika sudah dibayar, dan stok dipulihkan.']);
    }

    public function pay(Request $request, Order $order)
    {
        $order = DB::transaction(function () use ($request, $order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->buyer_id === $request->user()->id, 403);
            abort_if($locked->fulfillment_type === 'direct_deal', 409, 'Pembayaran ambil langsung diatur dengan penjual.');
            abort_unless($locked->order_status === 'pending' && $locked->payment_status === 'unpaid', 409, 'Pesanan ini tidak menunggu pembayaran.');

            $buyer = User::whereKey($locked->buyer_id)->lockForUpdate()->firstOrFail();
            if ((float) $buyer->wallet_balance < (float) $locked->total_amount) {
                throw ValidationException::withMessages(['wallet' => 'Saldo tidak mencukupi untuk membayar pesanan ini.']);
            }

            $buyer->decrement('wallet_balance', $locked->total_amount);
            $locked->update(['payment_status' => 'paid_escrow', 'paid_at' => now()]);
            WalletTransaction::create([
                'user_id' => $buyer->id,
                'order_id' => $locked->id,
                'type' => 'debit',
                'amount' => $locked->total_amount,
                'description' => "Pembayaran pesanan {$locked->order_number}",
            ]);

            return $locked->fresh();
        });

        return response()->json(['order' => $order, 'message' => 'Pembayaran berhasil. Dana ditahan sampai pesanan selesai.']);
    }

    private function refundIfPaid(Order $order): void
    {
        if ($order->payment_status !== 'paid_escrow') {
            return;
        }

        $buyer = User::whereKey($order->buyer_id)->lockForUpdate()->firstOrFail();
        $buyer->increment('wallet_balance', $order->total_amount);
        $order->update(['payment_status' => 'refunded']);
        WalletTransaction::create([
            'user_id' => $buyer->id,
            'order_id' => $order->id,
            'type' => 'credit',
            'amount' => $order->total_amount,
            'description' => "Pengembalian dana pesanan {$order->order_number}",
        ]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'fulfillment_type' => 'required|in:direct_deal,expedition',
            'shipping_quote_id' => 'required_if:fulfillment_type,expedition|nullable|integer',
        ]);

        $order = DB::transaction(function () use ($request, $data) {
            $product = Product::where('status', 'active')->lockForUpdate()->findOrFail($data['product_id']);
            abort_if($product->user_id === $request->user()->id, 422, 'Tidak dapat membeli produk sendiri.');
            abort_if($product->stock < 1, 409, 'Stok produk sudah habis.');

            $quote = null;
            $shipping = 0;
            if ($data['fulfillment_type'] === 'expedition') {
                $quote = DB::table('shipping_quotes')->where('id', $data['shipping_quote_id'])
                    ->where('user_id', $request->user()->id)
                    ->where('product_id', $product->id)
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();
                abort_unless($quote, 422, 'Tarif kurir sudah kedaluwarsa. Hitung ulang ongkir.');
                $shipping = (float) $quote->price;
            }

            $order = Order::create([
                'order_number' => 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'buyer_id' => $request->user()->id,
                'product_id' => $product->id,
                'fulfillment_type' => $data['fulfillment_type'],
                'shipping_address' => $quote ? json_decode($quote->destination, true) : null,
                'shipping_courier' => $quote ? trim($quote->courier_name.' '.$quote->service_name) : null,
                'item_amount' => $product->system_price,
                'shipping_amount' => $shipping,
                'total_amount' => (float) $product->system_price + $shipping,
                'order_status' => 'pending',
            ]);

            $product->decrement('stock');

            if ($quote) {
                DB::table('shipping_quotes')->where('id', $quote->id)->update(['used_at' => now(), 'updated_at' => now()]);
            }

            return $order;
        });

        return response()->json($order, 201);
    }

    public function ship(Request $request, Order $order)
    {
        abort_unless($order->product?->user_id === $request->user()->id, 403);
        abort_unless($order->fulfillment_type === 'expedition' && in_array($order->order_status, ['pending', 'processing'], true), 409);
        abort_unless($order->payment_status === 'paid_escrow', 409, 'Pembeli harus membayar pesanan sebelum paket dikirim.');
        $data = $request->validate(['tracking_number' => 'nullable|string|max:100']);
        $order->update(['tracking_number' => $data['tracking_number'] ?? null, 'order_status' => 'shipped']);
        return $order->fresh();
    }

    public function directHandoff(Request $request, Order $order)
    {
        abort_unless($order->product?->user_id === $request->user()->id, 403);
        abort_unless($order->fulfillment_type === 'direct_deal', 409);
        $data = $request->validate(['action' => 'required|in:start,complete']);
        if ($data['action'] === 'start') {
            abort_unless($order->order_status === 'pending', 409);
            abort_unless(in_array($order->payment_status, ['unpaid', 'paid_escrow'], true), 409, 'Pesanan tidak dapat memulai serah terima.');
            $order->update(['order_status' => 'processing']);
        } else {
            abort_unless($order->order_status === 'processing', 409);
            $order->update(['order_status' => 'delivered', 'delivered_at' => now()]);
        }
        return $order->fresh();
    }

    public function confirm(Request $request, Order $order, WalletService $wallet)
    {
        $order = DB::transaction(function () use ($request, $order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->buyer_id === $request->user()->id, 403);
            abort_unless(in_array($locked->order_status, ['shipped', 'delivered'], true), 409, 'Pesanan belum dapat dikonfirmasi sebagai diterima.');
            $directUnpaid = $locked->fulfillment_type === 'direct_deal' && $locked->payment_status === 'unpaid';
            abort_unless($locked->payment_status === 'paid_escrow' || $directUnpaid, 409, 'Pembayaran pesanan tidak ditemukan.');

            if ($directUnpaid) {
                $locked->update(['order_status' => 'completed', 'delivered_at' => $locked->delivered_at ?? now()]);
                return $locked->fresh();
            }

            if ($locked->order_status === 'shipped') {
                $locked->update(['order_status' => 'delivered', 'delivered_at' => now()]);
            }

            return $locked->fresh();
        });

        if ($order->payment_status === 'paid_escrow') {
            $wallet->release($order);
        }
        return $order->fresh();
    }

    public function report(Request $request, Order $order)
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);
        $data = $request->validate(['reason' => 'required|string|max:5000']);
        abort_unless($order->payment_status === 'paid_escrow', 409);
        $order->reports()->create(['reporter_id' => $request->user()->id, 'reason' => $data['reason'], 'status' => 'open']);
        $order->update(['order_status' => 'disputed']);
        return response()->json(['message' => 'Laporan tercatat; dana ditahan selama penyelidikan.'], 201);
    }
}
