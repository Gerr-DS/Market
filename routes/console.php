<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Schedule::call(function () {
    Product::with('images')
        ->where('type', 'food')
        ->whereIn('status', ['active', 'pending_approval'])
        ->whereDate('expired_at', '<=', today()->addDays(7))
        ->each(function (Product $product) {
            Storage::disk('public')->delete($product->images->pluck('image_path')->all());
            $product->forceDelete();
        });
})->daily();

Schedule::call(function () {
    Order::where('order_status', 'delivered')
        ->where('payment_status', 'paid_escrow')
        ->whereNotNull('delivered_at')
        ->where('delivered_at', '<=', now()->subHours(72))
        ->whereDoesntHave('reports', fn ($query) => $query->whereIn('status', ['open', 'investigating']))
        ->each(fn (Order $order) => app(WalletService::class)->release($order));
})->hourly();

Schedule::call(function () {
    Order::where('order_status', 'pending')
        ->where('payment_status', 'paid_escrow')
        ->where('paid_at', '<=', now()->subDays(3))
        ->each(function (Order $order) {
            DB::transaction(function () use ($order) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->first();
                if (!$locked || $locked->order_status !== 'pending' || $locked->payment_status !== 'paid_escrow' || !$locked->paid_at || $locked->paid_at->gt(now()->subDays(3))) {
                    return;
                }

                $buyer = User::whereKey($locked->buyer_id)->lockForUpdate()->first();
                if ($buyer) {
                    $buyer->increment('wallet_balance', $locked->total_amount);
                    WalletTransaction::create([
                        'user_id' => $buyer->id,
                        'order_id' => $locked->id,
                        'type' => 'credit',
                        'amount' => $locked->total_amount,
                        'description' => "Pengembalian dana otomatis pesanan {$locked->order_number} yang tidak dikonfirmasi penjual",
                    ]);
                }

                $locked->update(['payment_status' => 'refunded', 'order_status' => 'cancelled']);
                Product::withTrashed()->whereKey($locked->product_id)->increment('stock');
            });
        });
})->everyMinute();
